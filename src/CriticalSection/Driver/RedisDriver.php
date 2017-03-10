<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

use Redis;
use stekycz\CriticalSection\Exception\CriticalSectionException;
use Throwable;

class RedisDriver implements IDriver
{

	const LOCK_VALUE = 1;

	/**
	 * @var Redis
	 */
	private $redis;

	/**
	 * @var int
	 */
	private $acquireTimeout;

	public function __construct(Redis $redis, int $acquireTimeout = 1)
	{
		$this->redis = $redis;
		$this->acquireTimeout = $acquireTimeout;
	}

	public function acquireLock(string $label) : bool
	{
		$lockKey = self::formatLock($label);

		// This condition assumes one redis instance only that guarantees atomicity
		if ($this->redis->sAdd($lockKey . ':initialized', $lockKey) > 0) {
			$multiResult = $this->redis->multi()
				->del($lockKey)
				->del($lockKey . ':released')
				->rPush($lockKey, self::LOCK_VALUE)
				->sAdd($lockKey . ':released', 1)
				->exec();
			if ($multiResult[2] === FALSE) {
				throw new CriticalSectionException('Cannot initialize redis critical section on first enter for "' . $label . '".');
			}
		}
		try {
			$result = $this->redis->multi()
				->blPop($lockKey, $this->acquireTimeout)
				->sRem($lockKey . ':released', 1)
				->exec();
		} catch (Throwable $e) {
			throw new CriticalSectionException('Could not acquire redis critical section lock for "' . $label . '".', 0, $e);
		}
		if (!$result[0]) {
			return FALSE;
		}

		return TRUE;
	}

	public function releaseLock(string $label) : bool
	{
		$lockKey = self::formatLock($label);
		if (!$this->redis->sIsMember($lockKey . ':initialized', $lockKey)) {
			return FALSE;
		}
		if ($this->redis->sIsMember($lockKey . ':released', 1)) {
			return FALSE;
		}

		$result = $this->redis->multi()
			->rPush($lockKey, self::LOCK_VALUE)
			->sAdd($lockKey . ':released', 1)
			->exec();

		return $result[0] === FALSE ? FALSE : TRUE;
	}

 	private static function formatLock(string $label) : string
	{
		return $label . ':lock';
	}

}
