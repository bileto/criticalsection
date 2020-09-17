<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

use PDO;
use PDOStatement;

/**
 * @see https://www.postgresql.org/docs/9.4/static/functions-admin.html#FUNCTIONS-ADVISORY-LOCKS-TABLE
 */
class PdoPgsqlDriver implements IDriver
{

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function acquireLock(string $label): bool
    {
        $lockId = self::transformLabelToIntegerKey($label);

        return $this->runQuery('SELECT pg_try_advisory_lock(?)', $lockId);
    }

    public function releaseLock(string $label): bool
    {
        $lockId = self::transformLabelToIntegerKey($label);

        return $this->runQuery('SELECT pg_advisory_unlock(?)', $lockId);
    }

    private static function transformLabelToIntegerKey(string $label): int
    {
        return crc32($label);
    }

    /**
     * @param string $query
     * @param int $lockId
     * @return bool
     * @throws PDOException Thrown if error mode set to PDO::ERRMODE_EXCEPTION,
     * and an error occures
     */
    private function runQuery(string $query, int $lockId): bool
    {
        /** @var PDOStatement|bool $statement */
        $statement = $this->pdo->prepare($query);
        if (is_bool($statement) && $statement === false) {
            return false;
        }

        $executionResult = $statement->execute([$lockId]);

        return $executionResult
            ? (bool)$statement->fetch(PDO::FETCH_COLUMN)
            : false;
    }

}
