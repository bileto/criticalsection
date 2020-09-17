<?php

declare(strict_types=1);

namespace CriticalSectionTests\Driver;

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

    protected function setUp()
    {
        $this->driver = new SemaphoreDriver();
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testCanAcquireOnce()
    {
        $label = __FUNCTION__;
        Assert::true($this->driver->acquireLock($label));
    }

    public function testCanReleaseOnce()
    {
        $label = __FUNCTION__;
        Assert::true($this->driver->acquireLock($label));
        Assert::true($this->driver->releaseLock($label));
    }

}

(new SemaphoreDriverTest())->run();
