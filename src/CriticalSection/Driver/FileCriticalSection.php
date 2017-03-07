<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

use RuntimeException;
use stekycz\CriticalSection\CriticalSection;

class FileCriticalSection extends CriticalSection
{

	/**
	 * @var resource[]
	 */
	private $handles = [];

	/**
	 * @var string
	 */
	private $lockFilesDir;

	public function __construct(string $lockFilesDir)
	{
		$lockFilesDir = rtrim($lockFilesDir, DIRECTORY_SEPARATOR);
		self::createDir($lockFilesDir);
		$this->lockFilesDir = $lockFilesDir;
	}

	protected function acquireLock(string $label) : bool
	{
		$handle = fopen($this->getFilePath($label), "w+b");
		if ($handle === FALSE) {
			return FALSE;
		}

		$locked = flock($handle, LOCK_EX | LOCK_NB);
		if ($locked === FALSE) {
			fclose($handle);

			return FALSE;
		}

		$this->handles[$label] = $handle;

		return TRUE;
	}

	protected function releaseLock(string $label) : bool
	{
		$unlocked = flock($this->handles[$label], LOCK_UN);
		if ($unlocked === FALSE) {
			return FALSE;
		}

		fclose($this->handles[$label]);

		return TRUE;
	}

	private function getFilePath(string $label) : string
	{
		return $this->lockFilesDir . DIRECTORY_SEPARATOR . sha1($label);
	}

	private static function createDir(string $dir)
	{
		if (!is_dir($dir) && !@mkdir($dir, 0777, TRUE) && !is_dir($dir)) { // @ - dir may already exist
			throw new RuntimeException("Unable to create directory '$dir'. " . error_get_last()['message']);
		}
	}

}
