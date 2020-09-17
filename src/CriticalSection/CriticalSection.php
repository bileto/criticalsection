<?php

declare(strict_types=1);

namespace Bileto\CriticalSection;

use Bileto\CriticalSection\Driver\IDriver;
use Bileto\CriticalSection\Exception\RuntimeException;
use Throwable;

final class CriticalSection implements ICriticalSection
{

	/**
	 * @var IDriver
	 */
	private $driver;

	/**
	 * @var bool[]
	 */
	private $locks = [];

	public function __construct(IDriver $driver)
	{
		$this->driver = $driver;
	}

	public function __destruct()
	{
		/** @var Throwable[] $exceptions */
		$exceptions = [];
		foreach ($this->locks as $label => $isLocked) {
			try {
				$this->leave($label);
			} catch (Throwable $e) {
				$exceptions[] = $e;
			}
		}

		if (count($exceptions) === 1) {
			throw $exceptions[0];
		} elseif (count($exceptions) > 1) {
			$messages = '';
			foreach ($exceptions as $index => $e) {
				$messages .= $index . ': ' . $e->getMessage() . "\n";
			}
			throw new RuntimeException('Thrown too many exceptions during destruction of CriticalSection. Messages:' . "\n" . $messages);
		}
	}

	public function enter(string $label) : bool
	{
		if ($this->isEntered($label)) {
			return FALSE;
		}

		$result = $this->driver->acquireLock($label);
		if ($result) {
			$this->locks[$label] = $result;
		}

		return $result;
	}

	public function leave(string $label) : bool
	{
		if (!$this->isEntered($label)) {
			return FALSE;
		}

		$result = $this->driver->releaseLock($label);
		if ($result) {
			unset($this->locks[$label]);
		}

		return $result;
	}

	public function isEntered(string $label) : bool
	{
		return array_key_exists($label, $this->locks) && $this->locks[$label] === TRUE;
	}

}
