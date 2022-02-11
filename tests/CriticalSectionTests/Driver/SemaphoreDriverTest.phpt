<?php

declare(strict_types=1);

namespace BiletoTests\CriticalSectionTests\Driver;

require_once(__DIR__ . '/../bootstrap.php');

use Bileto\CriticalSection\Driver\SemaphoreDriver;
use Mockery;
use Tester\TestCase;
use Tester\Assert;

class SemaphoreDriverTest extends TestCase
{

    const TEST_LABEL = "test";

    /** @var SemaphoreDriver */
    private $driver;

    protected function setUp(): void
    {
        $this->driver = new SemaphoreDriver();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanAcquireOnce(): void
    {
        $label = __FUNCTION__;
        Assert::true($this->driver->acquireLock($label));
    }

    public function testCanReleaseOnce(): void
    {
        $label = __FUNCTION__;
        Assert::true($this->driver->acquireLock($label));
        Assert::true($this->driver->releaseLock($label));
    }

}

(new SemaphoreDriverTest())->run();
