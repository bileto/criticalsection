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
        $lockKey = self::formatLock($label);

        // This condition assumes one redis instance only that guarantees atomicity
        if ($this->redis->sAdd($lockKey . ':initialized', $lockKey) > 0) {
            $multiResult = $this->redis->multi();
            $multiResult->del($lockKey);
            $multiResult->del($lockKey . ':released');
            $multiResult->rPush($lockKey, self::LOCK_VALUE);
            $multiResult->sAdd($lockKey . ':released', 1);
            $multiResult->exec();

            if ($multiResult[2] === false) {
                throw new CriticalSectionException('Cannot initialize redis critical section on first enter for "' . $label . '".');
            }
        }
        try {
            $result = $this->redis->multi();
            $result->blPop($lockKey, $this->acquireTimeout);
            $result->sRem($lockKey . ':released', 1);
            $result->exec();
        } catch (Throwable $e) {
            throw new CriticalSectionException('Could not acquire redis critical section lock for "' . $label . '".', 0, $e);
        }

        if (!$result[0]) {
            return false;
        }

        return TRUE;
    }

    public function releaseLock(string $label): bool
    {
        $lockKey = self::formatLock($label);
        if (!$this->redis->sIsMember($lockKey . ':initialized', $lockKey)) {
            return false;
        }
        if ($this->redis->sIsMember($lockKey . ':released', 1)) {
            return false;
        }

        $result = $this->redis->multi();
        $result->rPush($lockKey, self::LOCK_VALUE);
        $result->sAdd($lockKey . ':released', 1);
        $result->exec();

        return $result[0] === false ? false : true;
    }

    private static function formatLock(string $label): string
    {
        return $label . ':lock';
    }

}
