<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\CriticalSection\tests\Driver;

use Mockery;
use PDO;
use PDOStatement;
use stekycz\CriticalSection\Driver\PdoMysqlDriver;
use TestCase;
use Tester\Assert;

require_once(__DIR__ . '/../bootstrap.php');

class PdoMysqlDriverTest extends TestCase
{

	const TEST_LABEL = 'test';

	/**
	 * @var PdoMysqlDriver
	 */
	private $driver;

	/**
	 * @var PDO
	 */
	private $pdo;

	protected function setUp()
	{
		parent::setUp();
		$this->pdo = Mockery::mock(PDO::class);
		$this->driver = new PdoMysqlDriver($this->pdo);
	}

	public function testCanAcquireOnce()
	{
		$statement = Mockery::mock(PDOStatement::class);
		$statement->shouldReceive('execute')->once()->andReturn(TRUE);
		$statement->shouldReceive('fetch')->once()->andReturn('1');

		$this->pdo->shouldReceive('prepare')->once()->andReturn($statement);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
	}

	public function testCanReleaseOnce()
	{
		$statement = Mockery::mock(PDOStatement::class);
		$statement->shouldReceive('execute')->twice()->andReturn(TRUE);
		$statement->shouldReceive('fetch')->twice()->andReturn('1');

		$this->pdo->shouldReceive('prepare')->twice()->andReturn($statement);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
	}

}

run(new PdoMysqlDriverTest());
