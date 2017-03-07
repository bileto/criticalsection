<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\CriticalSection\tests\Driver;

use Mockery;
use Redis;
use stekycz\CriticalSection\Driver\RedisCriticalSection;
use TestCase;
use Tester\Assert;

require_once(__DIR__ . '/../bootstrap.php');

class RedisCriticalSectionTest extends TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var RedisCriticalSection
	 */
	private $criticalSection;

	/**
	 * @var Redis|Mockery\MockInterface
	 */
	private $redis;

	protected function setUp()
	{
		parent::setUp();
		$this->redis = Mockery::mock(Redis::class);
		$this->criticalSection = new RedisCriticalSection($this->redis);
	}

	protected function tearDown()
	{
		if ($this->criticalSection->isEntered(self::TEST_LABEL)) {
			$this->criticalSection->leave(self::TEST_LABEL);
		}
		parent::tearDown();
	}

	public function testCanBeEnteredAndLeaved()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(1);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, 1]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
	}

	public function testCannotBeEnteredTwice()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(1);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, 1]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($this->criticalSection->enter(self::TEST_LABEL));
	}

	public function testCannotBeLeavedWithoutEnter()
	{
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($this->criticalSection->leave(self::TEST_LABEL));
	}

	public function testCannotBeLeavedTwice()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(1);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, 1]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);

		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->leave(self::TEST_LABEL));
	}

	public function testMultipleCriticalSectionHandlers()
	{
		$this->redis->shouldReceive('sAdd')->once()->andReturn(1);
		$this->redis->shouldReceive('sAdd')->once()->andReturn(0);
		$this->redis->shouldReceive('multi')->once()->andReturnSelf();
		$this->redis->shouldReceive('del')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturnSelf();
		$this->redis->shouldReceive('rPush')->once()->andReturn(1);
		$this->redis->shouldReceive('exec')->once()->andReturn([0, 1]);
		$this->redis->shouldReceive('blPop')->once()->andReturn(1);
		$this->redis->shouldReceive('blPop')->once()->andReturn(null);

		$criticalSection = $this->criticalSection;
		$criticalSection2 = new RedisCriticalSection($this->redis);

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

run(new RedisCriticalSectionTest());
