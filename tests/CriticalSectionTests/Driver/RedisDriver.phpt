<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace CriticalSectionTests\Driver;

use Exception;
use Mockery;
use Redis;
use Bileto\CriticalSection\Driver\RedisDriver;
use Bileto\CriticalSection\Exception\CriticalSectionException;
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
	private $redisMock;

	/**
	 * @var Redis
	 */
	private $redis;

	protected function setUp()
	{
		parent::setUp();
		$this->redisMock = Mockery::mock(Redis::class);
		$redis = new Redis();
		$redis->connect('127.0.0.1');
		$redis->select(7);
		$this->redis = $redis;
	}

	public function testCanAcquireOnce()
	{
		$label = __FUNCTION__;
		$driver = new RedisDriver($this->redis);
		Assert::true($driver->acquireLock($label));
		Assert::true($driver->releaseLock($label));
	}

	public function testCanReleaseOnceAndOnlyOnce()
	{
		$label = __FUNCTION__;
		$driver = new RedisDriver($this->redis);
		Assert::true($driver->acquireLock($label));
		Assert::true($driver->releaseLock($label));
		Assert::false($driver->releaseLock($label));
	}

	public function testCanAcquireAndReleaseMultipleTimes()
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

	public function testUnsuccessfulAcquire()
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

	public function testUnsuccessfulReleaseBecauseOfRPush()
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

	public function testUnsuccessfulReleaseBecauseOfNoAcquire()
	{
		$label = __FUNCTION__;
		$driver = new RedisDriver($this->redis);
		Assert::false($driver->releaseLock($label));
	}

	public function testCannotInitializeCriticalSectionOnFirstEnter()
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
			$driver->acquireLock(self::TEST_LABEL);
		}, CriticalSectionException::class,'Cannot initialize redis critical section on first enter for "' . self::TEST_LABEL . '".');
	}

	public function testExceptionOnLockAcquire()
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
			$driver->acquireLock(self::TEST_LABEL);
		}, CriticalSectionException::class, 'Could not acquire redis critical section lock for "' . self::TEST_LABEL . '".');
	}

}

run(new RedisDriverTest());
