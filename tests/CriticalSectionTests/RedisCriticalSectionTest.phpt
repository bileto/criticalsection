<?php

declare(strict_types=1);

namespace BiletoTests\CriticalSectionTests;

require_once(__DIR__ . '/bootstrap.php');

use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Redis;
use Bileto\CriticalSection\CriticalSection;
use Bileto\CriticalSection\Driver\IDriver;
use Tester\TestCase;
use Tester\Assert;

class RedisCriticalSectionTest extends TestCase
{

    const TEST_LABEL = "test";

    /** @var CriticalSection */
    private $criticalSection;

    /** @var IDriver|MockInterface|LegacyMockInterface */
    private $driver;

    protected function setUp(): void
    {
        $this->driver = Mockery::mock(IDriver::class);
        $this->criticalSection = new CriticalSection($this->driver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanBeEnteredAndLeft(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(TRUE);
        $this->driver->shouldReceive('releaseLock')->once()->andReturn(TRUE);

        Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::true($this->criticalSection->enter(self::TEST_LABEL));
        Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::true($this->criticalSection->leave(self::TEST_LABEL));
        Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
    }

    public function testCannotBeEnteredTwice(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(TRUE);
        $this->driver->shouldReceive('releaseLock')->once()->andReturn(TRUE);

        Assert::true($this->criticalSection->enter(self::TEST_LABEL));
        Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($this->criticalSection->enter(self::TEST_LABEL));
        Assert::true($this->criticalSection->leave(self::TEST_LABEL));
    }

    public function testCannotBeLeftWithoutEnter(): void
    {
        $this->driver->shouldReceive('acquireLock')->never();
        $this->driver->shouldReceive('releaseLock')->never();

        Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($this->criticalSection->leave(self::TEST_LABEL));
    }

    public function testCannotBeLeftTwice(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(TRUE);
        $this->driver->shouldReceive('releaseLock')->once()->andReturn(TRUE);

        Assert::true($this->criticalSection->enter(self::TEST_LABEL));
        Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::true($this->criticalSection->leave(self::TEST_LABEL));
        Assert::false($this->criticalSection->leave(self::TEST_LABEL));
    }

    public function testIsNotEnteredOnNotAcquiredLock(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(FALSE);
        $this->driver->shouldReceive('releaseLock')->never();

        Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($this->criticalSection->enter(self::TEST_LABEL));
        Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
    }

    public function testIsNotLeftOnNotReleasedLock(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(TRUE);
        $this->driver->shouldReceive('releaseLock')->once()->andReturn(FALSE);

        Assert::true($this->criticalSection->enter(self::TEST_LABEL));
        Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($this->criticalSection->leave(self::TEST_LABEL));
        Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
    }

    public function testMultipleCriticalSectionHandlers(): void
    {
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(TRUE);
        $this->driver->shouldReceive('acquireLock')->once()->andReturn(FALSE);
        $this->driver->shouldReceive('releaseLock')->once()->andReturn(TRUE);

        $criticalSection = $this->criticalSection;
        $criticalSection2 = new CriticalSection($this->driver);

        Assert::false($criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
        Assert::true($criticalSection->enter(self::TEST_LABEL));
        Assert::false($criticalSection2->enter(self::TEST_LABEL));
        Assert::true($criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
        Assert::true($criticalSection->leave(self::TEST_LABEL));
        Assert::false($criticalSection2->leave(self::TEST_LABEL));
        Assert::false($criticalSection->isEntered(self::TEST_LABEL));
        Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
    }

}

(new RedisCriticalSectionTest())->run();

