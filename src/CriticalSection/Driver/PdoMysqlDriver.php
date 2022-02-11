<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

use PDO;
use PDOException;
use PDOStatement;

/**
 * @see https://mariadb.com/kb/en/mariadb/get_lock
 * @see https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
 */
class PdoMysqlDriver implements IDriver
{
    const NO_WAIT = 0;

    /** @var PDO */
    private $pdo;

    /** @var int */
    private $lockTimeout;

    public function __construct(PDO $pdo, int $lockTimeout = self::NO_WAIT)
    {
        $this->pdo = $pdo;
        $this->lockTimeout = $lockTimeout;
    }

    public function acquireLock(string $label): bool
    {
        $lockName = self::transformLabelToKey($label);

        return $this->runQuery('SELECT GET_LOCK(?, ?)', $lockName, $this->lockTimeout);
    }

    public function releaseLock(string $label): bool
    {
        $lockName = self::transformLabelToKey($label);

        return $this->runQuery('SELECT RELEASE_LOCK(?)', $lockName);
    }

    private static function transformLabelToKey(string $label): string
    {
        return hash('sha256', $label);
    }

    /**
     * @param string $query
     * @param string $lockName
     * @param int|null $lockTimeout
     * @return bool
     * @throws PDOException Thrown if error mode set to PDO::ERRMODE_EXCEPTION,
     * and an error occures
     */
    private function runQuery(string $query, string $lockName, int $lockTimeout = NULL): bool
    {
        /** @var PDOStatement|bool $statement */
        $statement = $this->pdo->prepare($query);
        if (is_bool($statement)) {
            return false;
        }

        $executeParameters = [$lockName];
        if ($lockTimeout !== null) {
            $executeParameters[] = $lockTimeout;
        }

        $executionResult = $statement->execute($executeParameters);

        return $executionResult
            ? (bool)$statement->fetch(PDO::FETCH_COLUMN)
            : false;
    }

}
