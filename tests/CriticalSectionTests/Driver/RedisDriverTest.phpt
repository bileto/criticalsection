<?php

declare(strict_types=1);

namespace BiletoTests\CriticalSectionTests\Driver;

require_once(__DIR__ . '/../bootstrap.php');

use Exception;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Redis;
use Bileto\CriticalSection\Driver\RedisDriver;
use Bileto\CriticalSection\Exception\CriticalSectionException;
use Tester\TestCase;
use Tester\Assert;

class RedisDriverTest extends TestCase
{

    const TEST_LABEL = "test";

    /** @var RedisDriver */
    private $driver;

    /** @var Redis|MockInterface|LegacyMockInterface */
    private $redisMock;

    /** @var Redis */
    private $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisMock = Mockery::mock(Redis::class);
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->select(7);
        $this->redis = $redis;
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanAcquireOnce(): void
    {
        $label = __FUNCTION__;
        $driver = new RedisDriver($this->redis);
        Assert::true($driver->acquireLock($label));
        Assert::true($driver->releaseLock($label));
    }

    public function testCanReleaseOnceAndOnlyOnce(): void
    {
        $label = __FUNCTION__;
        $driver = new RedisDriver($this->redis);
        Assert::true($driver->acquireLock($label));
        Assert::true($driver->releaseLock($label));
        Assert::false($driver->releaseLock($label));
    }

    public function testCanAcquireAndReleaseMultipleTimes(): void
    {
        $label = __FUNCTION__;
        $driver = new RedisDriver($this->redis);
        Assert::true($driver->acquireLock($label));
        Assert::true($driver->releaseLock($label));
        Assert::true($driver->acquireLock($label));
        Assert::true($driver->releaseLock($label));
        Assert::true($driver->acquireLock($label));
        Assert::true($driver->releaseLock($label));
    }

    public function testUnsuccessfulAcquire(): void
    {
        $this->redisMock->shouldReceive('sAdd')->once()->andReturn(1);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->twice()->andReturnSelf();
        $this->redisMock->shouldReceive('rPush')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('sAdd')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([0, 0, TRUE, 1]);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('blPop')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('srem')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([0, 1]);

        $driver = new RedisDriver($this->redisMock);
        Assert::false($driver->acquireLock(self::TEST_LABEL));
    }

    public function testUnsuccessfulReleaseBecauseOfRPush(): void
    {
        $this->redisMock->shouldReceive('sAdd')->once()->andReturn(1);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->twice()->andReturnSelf();
        $this->redisMock->shouldReceive('rPush')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('sAdd')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([0, 0, TRUE, 1]);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('blPop')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('srem')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([1, 1]);
        $this->redisMock->shouldReceive('sismember')->once()->andReturn(TRUE);
        $this->redisMock->shouldReceive('sismember')->once()->andReturn(FALSE);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('rPush')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('sAdd')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([FALSE, 1]);

        $driver = new RedisDriver($this->redisMock);
        Assert::true($driver->acquireLock(self::TEST_LABEL));
        Assert::false($driver->releaseLock(self::TEST_LABEL));
    }

    public function testUnsuccessfulReleaseBecauseOfNoAcquire(): void
    {
        $label = __FUNCTION__;
        $driver = new RedisDriver($this->redis);
        Assert::false($driver->releaseLock($label));
    }

    public function testCannotInitializeCriticalSectionOnFirstEnter(): void
    {
        $this->redisMock->shouldReceive('sAdd')->once()->andReturn(1);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('rPush')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('sAdd')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([0, 0, FALSE, 1]);

        Assert::exception(function () {
            $driver = new RedisDriver($this->redisMock);
            $driver->acquireLock(static::TEST_LABEL);
        }, CriticalSectionException::class, "Cannot initialize redis critical section on first enter for '" . static::TEST_LABEL . "'.");
    }

    public function testExceptionOnLockAcquire(): void
    {
        $this->redisMock->shouldReceive('sAdd')->once()->andReturn(1);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('del')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('rPush')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('sAdd')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('exec')->once()->andReturn([0, 0, TRUE, 1]);
        $this->redisMock->shouldReceive('multi')->once()->andReturnSelf();
        $this->redisMock->shouldReceive('blPop')->once()->andThrow(Exception::class);

        Assert::exception(function () {
            $driver = new RedisDriver($this->redisMock);
            $driver->acquireLock(static::TEST_LABEL);
        }, CriticalSectionException::class, "Could not acquire redis critical section lock for '" . static::TEST_LABEL . "'.");
    }

}

(new RedisDriverTest())->run();
