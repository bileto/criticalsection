<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

use PDO;

/**
 * @see https://mariadb.com/kb/en/mariadb/get_lock
 * @see https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
 */
class PdoMysqlDriver implements IDriver
{
	const NO_WAIT = 0;

	/**
	 * @var PDO
	 */
	private $pdo;

	/**
	 * @var int
	 */
	private $lockTimeout;

	public function __construct(PDO $pdo, int $lockTimeout = self::NO_WAIT)
	{
		$this->pdo = $pdo;
		$this->lockTimeout = $lockTimeout;
	}

	public function acquireLock(string $label) : bool
	{
		$lockName = self::transformLabelToKey($label);

		return $this->runQuery('SELECT GET_LOCK(?, ?)', $lockName, $this->lockTimeout);
	}

	public function releaseLock(string $label) : bool
	{
		$lockName = self::transformLabelToKey($label);

		return $this->runQuery('SELECT RELEASE_LOCK(?)', $lockName);
	}

	private static function transformLabelToKey(string $label) : string
	{
		return sha1($label);
	}

	private function runQuery(string $query, string $lockName, int $lockTimeout = NULL) : bool
	{
		$statement = $this->pdo->prepare($query);
		if ($statement === FALSE) {
			return FALSE;
		}

		$executeParameters = [$lockName];
		if ($lockTimeout !== NULL) {
			$executeParameters[] = $lockTimeout;
		}
		$executionResult = $statement->execute($executeParameters);

		return $executionResult
			? (bool) $statement->fetch(PDO::FETCH_COLUMN)
			: FALSE;
	}

}
