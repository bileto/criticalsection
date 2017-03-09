<?php

declare(strict_types=1);

namespace stekycz\CriticalSection\Driver;

interface IDriver
{

	public function acquireLock(string $label) : bool;

	public function releaseLock(string $label) : bool;

}
