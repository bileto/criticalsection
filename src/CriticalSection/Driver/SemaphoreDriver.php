<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

/**
 * This driver works correctly in single system environment only because semaphores are not shared on different
 * machines or in different Docker containers.
 */
class SemaphoreDriver implements IDriver
{

	/**
	 * @var resource[]
	 */
	private $handles = [];

	public function acquireLock(string $label) : bool
	{
		$key = self::transformLabelToIntegerKey($label);

		$semaphore = sem_get($key);
		if (!$semaphore) {
			return FALSE;
		}

		$result = sem_acquire($semaphore, TRUE);
		if (!$result) {
			return FALSE;
		}

		$this->handles[$label] = $semaphore;

		return TRUE;
	}

	public function releaseLock(string $label) : bool
	{
		if (!isset($this->handles[$label])) {
			return FALSE;
		}

		$result = sem_release($this->handles[$label]);
		if (!$result) {
			return FALSE;
		}

		unset($this->handles[$label]);

		return TRUE;
	}

	private static function transformLabelToIntegerKey(string $label) : int
	{
		return crc32($label);
	}

}
