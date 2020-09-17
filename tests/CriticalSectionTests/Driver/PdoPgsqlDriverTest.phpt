<?php

declare(strict_types=1);

namespace CriticalSectionTests\Driver;

require_once(__DIR__ . '/../bootstrap.php');

use Mockery;
use PDO;
use PDOStatement;
use Bileto\CriticalSection\Driver\PdoPgsqlDriver;
use Tester\TestCase;
use Tester\Assert;

class PdoPgsqlDriverTest extends TestCase
{

    const TEST_LABEL = "test";

    /** @var PdoPgsqlDriver */
    private $driver;

    /** @var PDO */
    private $pdo;

    protected function setUp()
    {
        $this->pdo = Mockery::mock(PDO::class);
        $this->driver = new PdoPgsqlDriver($this->pdo);
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testCanAcquireOnce()
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andReturn(TRUE);
        $statement->shouldReceive('fetch')->once()->andReturn(TRUE);

        $this->pdo->shouldReceive('prepare')->once()->andReturn($statement);

        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
    }

    public function testCanReleaseOnce()
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->twice()->andReturn(TRUE);
        $statement->shouldReceive('fetch')->twice()->andReturn(TRUE);

        $this->pdo->shouldReceive('prepare')->twice()->andReturn($statement);

        Assert::true($this->driver->acquireLock(self::TEST_LABEL));
        Assert::true($this->driver->releaseLock(self::TEST_LABEL));
    }

}

(new PdoPgsqlDriverTest())->run();
