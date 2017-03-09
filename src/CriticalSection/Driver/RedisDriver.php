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
		if ($this->redis->sAdd($lockKey . ':control', $lockKey) > 0) {
			$multiResult = $this->redis->multi()
				->del($lockKey)
				->rPush($lockKey, self::LOCK_VALUE)
				->exec();
			if ($multiResult[1] === FALSE) {
				throw new CriticalSectionException('Cannot initialize redis critical section on first enter for "' . $label . '".');
			}
		}
		try {
			$result = $this->redis->blPop($lockKey, $this->acquireTimeout);
		} catch (Throwable $e) {
			throw new CriticalSectionException('Could not acquire redis critical section lock for "' . $label . '".', 0, $e);
		}
		if (!$result) {
			return FALSE;
		}

		return TRUE;
	}

	public function releaseLock(string $label) : bool
	{
		$lockKey = self::formatLock($label);
		$result = $this->redis->rPush($lockKey, self::LOCK_VALUE);

		return $result === FALSE ? FALSE : TRUE;
	}

 	private static function formatLock(string $label) : string
	{
		return $label . ':lock';
	}

}
