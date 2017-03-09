<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\CriticalSection\tests\Driver;

use Exception;
use Mockery;
use Redis;
use stekycz\CriticalSection\Driver\RedisDriver;
use stekycz\CriticalSection\Exception\CriticalSectionException;
use TestCase;
use Tester\Assert;

require_once(__DIR__ . '/../bootstrap.php');

class RedisDriverTest extends TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var RedisDriver
	 */
	private $driver;

	/**
	 * @var Redis|Mockery\MockInterface
	 */
	private $redis;

	protected function setUp()
	{
		parent::setUp();
		$this->redis = Mockery::mock(Redis::class);
		$this->driver = new RedisDriver($this->redis);
	}

	public function testCanAcquireOnce()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('exec')->once()->andReturn([0, TRUE]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
	}

	public function testCanReleaseOnce()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(TRUE);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, TRUE]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
	}

	public function testCanAcquireAndReleaseMultipleTimesWithOnlyOneInitialization()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('sAdd')->twice()->andReturn(0);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->with(self::TEST_LABEL . ':lock', 1)->andReturnSelf();
		$this->redis->shouldReceive('rPush')->times(3)->with(self::TEST_LABEL . ':lock', 1)->andReturn(TRUE);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, TRUE]);
		$this->redis->shouldReceive('blPop')->times(3)->andReturn(1);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
	}

	public function testUnsuccessfulAcquire()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('exec')->once()->andReturn([0, TRUE]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(0);

		Assert::false($this->driver->acquireLock(self::TEST_LABEL));
	}

	public function testUnsuccessfulRelease()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(FALSE);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, TRUE]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::false($this->driver->releaseLock(self::TEST_LABEL));
	}

	public function testCannotInitializeCriticalSectionOnFirstEnter()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('exec')->once()->andReturn([0, FALSE]);

		Assert::exception(function () {
			$this->driver->acquireLock(self::TEST_LABEL);
		}, CriticalSectionException::class,'Cannot initialize redis critical section on first enter for "' . self::TEST_LABEL . '".');
	}

	public function testExceptionOnLockAcquire()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('exec')->once()->andReturn([0, 1]);
		$this->redis->shouldReceive('blPop')->once()->andThrow(Exception::class);

		Assert::exception(function () {
			$this->driver->acquireLock(self::TEST_LABEL);
		}, CriticalSectionException::class, 'Could not acquire redis critical section lock for "' . self::TEST_LABEL . '".');
	}

}

run(new RedisDriverTest());
