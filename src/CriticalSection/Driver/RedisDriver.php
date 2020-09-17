<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

use Redis;
use Bileto\CriticalSection\Exception\CriticalSectionException;
use Throwable;

class RedisDriver implements IDriver
{

    const LOCK_VALUE = 1;

    /** @var Redis */
    private $redis;

    /** @var int */
    private $acquireTimeout;

    public function __construct(Redis $redis, int $acquireTimeout = 1)
    {
        $this->redis = $redis;
        $this->acquireTimeout = $acquireTimeout;
    }

    public function acquireLock(string $label): bool
    {
        $lockKey = static::keyForLock($label);

        // This condition assumes one redis instance only that guarantees atomicity
        /** @var bool|int $valueAdded */
        $valueAdded = $this->redis->sAdd($lockKey . ':initialized', $lockKey);
        if (!is_bool($valueAdded) && $valueAdded > 0) {
            $multiCall = $this->redis->multi();
            $multiCall->del($lockKey);
            $multiCall->del($lockKey . ':released');
            $multiCall->rPush($lockKey, self::LOCK_VALUE);
            $multiCall->sAdd($lockKey . ':released', 1);
            $multiResult = $multiCall->exec();

            if ($multiResult[2] === false) {
                throw new CriticalSectionException("Cannot initialize redis critical section on first enter for '{$label}'.");
            }
        }
        try {
            $multiCall = $this->redis->multi();
            $multiCall->blPop($lockKey, $this->acquireTimeout);
            $multiCall->sRem("{$lockKey}:released", 1);
            $multiResult = $multiCall->exec();
        } catch (Throwable $e) {
            throw new CriticalSectionException("Could not acquire redis critical section lock for '{$label}'.", 0, $e);
        }

        if (!$multiResult[0]) {
            return false;
        }

        return true;
    }

    public function releaseLock(string $label): bool
    {
        $lockKey = static::keyForLock($label);

        if (!$this->redis->sIsMember("{$lockKey}:initialized", $lockKey)) {
            return false;
        }
        if ($this->redis->sIsMember("{$lockKey}:released", 1)) {
            return false;
        }

        $multiCall = $this->redis->multi();
        $multiCall->rPush($lockKey, self::LOCK_VALUE);
        $multiCall->sAdd("{$lockKey}:released", 1);
        $multiResult = $multiCall->exec();

        return $multiResult[0] === false ? false : true;
    }

    public static function keyForLock(string $label): string
    {
        return "{$label}:lock";
    }

}
