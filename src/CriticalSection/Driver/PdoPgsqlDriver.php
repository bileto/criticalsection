<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

use PDO;

/**
 * @see https://www.postgresql.org/docs/9.4/static/functions-admin.html#FUNCTIONS-ADVISORY-LOCKS-TABLE
 */
class PdoPgsqlDriver implements IDriver
{

	/**
	 * @var PDO
	 */
	private $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function acquireLock(string $label) : bool
	{
		$lockId = self::transformLabelToIntegerKey($label);

		return $this->runQuery('SELECT pg_try_advisory_lock(?)', $lockId);
	}

	public function releaseLock(string $label) : bool
	{
		$lockId = self::transformLabelToIntegerKey($label);

		return $this->runQuery('SELECT pg_advisory_unlock(?)', $lockId);
	}

	private static function transformLabelToIntegerKey(string $label) : int
	{
		return crc32($label);
	}

	private function runQuery(string $query, int $lockId) : bool
	{
		$statement = $this->pdo->query($query);
		if ($statement === FALSE) {
			return FALSE;
		}

		$executionResult = $statement->execute([$lockId]);

		return $executionResult
			? (bool) $statement->fetch(PDO::FETCH_COLUMN)
			: FALSE;
	}

}
