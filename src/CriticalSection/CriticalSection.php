<?php

declare(strict_types=1);

namespace stekycz\CriticalSection;

abstract class CriticalSection implements ICriticalSection
{

	/**
	 * @var bool[]
	 */
	private $locks = [];

	public function __destruct()
	{
		foreach ($this->locks as $label => $isLocked) {
			$this->leave($label);
		}
	}

	final public function enter(string $label) : bool
	{
		if ($this->isEntered($label)) {
			return FALSE;
		}

		$result = $this->acquireLock($label);
		if ($result) {
			$this->locks[$label] = $result;
		}

		return $result;
	}

	final public function leave(string $label) : bool
	{
		if (!$this->isEntered($label)) {
			return FALSE;
		}

		$result = $this->releaseLock($label);
		if ($result) {
			unset($this->locks[$label]);
		}

		return $result;
	}

	final public function isEntered(string $label) : bool
	{
		return array_key_exists($label, $this->locks) && $this->locks[$label] === TRUE;
	}

	abstract protected function acquireLock(string $label) : bool;

	abstract protected function releaseLock(string $label) : bool;

}
