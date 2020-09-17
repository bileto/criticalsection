<?php

declare(strict_types=1);

namespace Bileto\CriticalSection;

interface ICriticalSection
{

	/**
	 * Enters critical section.
	 */
	public function enter(string $label) : bool;

	/**
	 * Leaves critical section.
	 */
	public function leave(string $label) : bool;

	/**
	 * Returns TRUE if critical section is entered.
	 */
	public function isEntered(string $label) : bool;

}
