<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

use Bileto\CriticalSection\Exception\CriticalSectionException;

class FileDriver implements IDriver
{

    /** @var array|resource[] */
    private $handles = [];

    /** @var string */
    private $lockFilesDir;

    public function __construct(string $lockFilesDir)
    {
        $lockFilesDir = rtrim($lockFilesDir, DIRECTORY_SEPARATOR);
        self::createDir($lockFilesDir);
        $this->lockFilesDir = $lockFilesDir;
    }

    public function acquireLock(string $label): bool
    {
        /** @var resource|bool $handle */
        $handle = fopen($this->getFilePath($label), "w+b");
        if (is_bool($handle) && $handle === false) {
            return false;
        }

        $locked = flock($handle, LOCK_EX | LOCK_NB);
        if ($locked === false) {
            fclose($handle);
        } else {
            $this->handles[$label] = $handle;
        }

        return $locked;
    }

    public function releaseLock(string $label): bool
    {
        if (array_key_exists($label, $this->handles) === false) {
            return false;
        }

        $unlocked = flock($this->handles[$label], LOCK_UN);
        if ($unlocked === false) {
            return false;
        }

        fclose($this->handles[$label]);
        unset($this->handles[$label]);

        return true;
    }

    private function getFilePath(string $label): string
    {
        return $this->lockFilesDir . DIRECTORY_SEPARATOR . sha1($label);
    }

    /**
     * @param string $dir
     * @throws CriticalSectionException
     */
    private static function createDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0777, TRUE) && !is_dir($dir)) { // @ - dir may already exist
            throw new CriticalSectionException("Unable to create directory '$dir'. " . error_get_last()['message']);
        }
    }

}
