<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\CriticalSection\tests\Driver;

use stekycz\CriticalSection\Driver\FileCriticalSection;
use TestCase;
use Tester\Assert;

require_once(__DIR__ . '/../bootstrap.php');

class FileCriticalSectionTest extends TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var FileCriticalSection
	 */
	private $criticalSection;

	/**
	 * @var string
	 */
	private $filesDir;

	protected function setUp()
	{
		parent::setUp();
		$this->filesDir = TEMP_DIR . "/critical-section";
		mkdir($this->filesDir, 0777, TRUE);
		$this->criticalSection = new FileCriticalSection($this->filesDir);
	}

	protected function tearDown()
	{
		if ($this->criticalSection->isEntered(self::TEST_LABEL)) {
			$this->criticalSection->leave(self::TEST_LABEL);
		}
		system('rm -rf ' . escapeshellarg($this->filesDir));
		parent::tearDown();
	}

	public function testCanBeEnteredAndLeaved()
	{
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
	}

	public function testCannotBeEnteredTwice()
	{
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
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->leave(self::TEST_LABEL));
	}

	public function testMultipleCriticalSectionHandlers()
	{
		$criticalSection = $this->criticalSection;
		$criticalSection2 = new FileCriticalSection($this->filesDir);

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

run(new FileCriticalSectionTest());
