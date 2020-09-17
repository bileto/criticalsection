<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

/**
 * This driver works correctly in single system environment only because semaphores are not shared on different
 * machines or in different Docker containers.
 */
class SemaphoreDriver implements IDriver
{

    /** @var array|resource[] */
    private $handles = [];

    public function acquireLock(string $label): bool
    {
        $key = self::transformLabelToIntegerKey($label);

        /** @var resource|bool $semaphore */
        $semaphore = sem_get($key);
        if (is_bool($semaphore) && $semaphore === false) {
            return false;
        }

        $result = sem_acquire($semaphore, true);
        if (!$result) {
            return false;
        }

        $this->handles[$label] = $semaphore;

        return true;
    }

    public function releaseLock(string $label): bool
    {
        if (array_key_exists($label, $this->handles) === false) {
            return false;
        }

        $result = sem_release($this->handles[$label]);
        if (!$result) {
            return false;
        }

        unset($this->handles[$label]);

        return true;
    }

    private static function transformLabelToIntegerKey(string $label): int
    {
        return crc32($label);
    }

}
