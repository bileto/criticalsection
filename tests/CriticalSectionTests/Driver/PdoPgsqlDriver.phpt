<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\CriticalSection\tests\Driver;

use Mockery;
use PDO;
use PDOStatement;
use stekycz\CriticalSection\Driver\PdoPgsqlDriver;
use TestCase;
use Tester\Assert;

require_once(__DIR__ . '/../bootstrap.php');

class PdoPgsqlDriverTest extends TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var PdoPgsqlDriver
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
		$this->driver = new PdoPgsqlDriver($this->pdo);
	}

	public function testCanAcquireOnce()
	{
		$statement = Mockery::mock(PDOStatement::class);
		$statement->shouldReceive('execute')->once()->andReturn(TRUE);
		$statement->shouldReceive('fetch')->once()->andReturn(TRUE);

		$this->pdo->shouldReceive('query')->once()->andReturn($statement);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
	}

	public function testCanReleaseOnce()
	{
		$statement = Mockery::mock(PDOStatement::class);
		$statement->shouldReceive('execute')->twice()->andReturn(TRUE);
		$statement->shouldReceive('fetch')->twice()->andReturn(TRUE);

		$this->pdo->shouldReceive('query')->twice()->andReturn($statement);

		Assert::true($this->driver->acquireLock(self::TEST_LABEL));
		Assert::true($this->driver->releaseLock(self::TEST_LABEL));
	}

}

run(new PdoPgsqlDriverTest());
