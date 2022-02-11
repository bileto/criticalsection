<?php

declare(strict_types=1);

namespace BiletoTests\CriticalSectionTests\Driver;

require_once(__DIR__ . '/../bootstrap.php');

use Bileto\CriticalSection\Driver\FileDriver;
use Bileto\CriticalSection\Exception\CriticalSectionException;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

class FileDriverTest extends TestCase
{

    const TEST_LABEL = "test";

    /** @var FileDriver */
    private $driver;

    /** @var string */
    private $filesDir;

    protected function setUp(): void
    {
        $this->filesDir = TEMP_DIR . '/critical-section';
        mkdir($this->filesDir, 0777, true);
        $this->driver = new FileDriver($this->filesDir);
    }

    protected function tearDown(): void
    {
        system('rm -rf ' . escapeshellarg($this->filesDir));
        Mockery::close();
    }

    public function testCanAcquireOnce(): void
    {
        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
    }

    public function testCanReleaseOnceAndOnlyOnce(): void
    {
        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
        Assert::true($this->driver->releaseLock(self::TEST_LABEL));
        Assert::false($this->driver->releaseLock(self::TEST_LABEL));
    }

    public function testReleaseWithoutAcquire(): void
    {
        Assert::false($this->driver->releaseLock(self::TEST_LABEL));
    }

    public function testCanAcquireAndReleaseMultipleTimes(): void
    {
        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
        Assert::true($this->driver->releaseLock(self::TEST_LABEL));
        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
        Assert::true($this->driver->releaseLock(self::TEST_LABEL));
        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
        Assert::true($this->driver->releaseLock(self::TEST_LABEL));
    }

    public function testCannotCreateDirectory(): void
    {
        $path = TEMP_DIR . '/file';
        touch($path);
        Assert::exception(function () use ($path) {
            new FileDriver($path);
        }, CriticalSectionException::class);
        unlink($path);
    }

}

(new FileDriverTest)->run();
