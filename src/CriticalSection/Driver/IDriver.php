<?php

declare(strict_types=1);

namespace Bileto\CriticalSection\Driver;

interface IDriver
{

    public function acquireLock(string $label): bool;

    public function releaseLock(string $label): bool;

}
