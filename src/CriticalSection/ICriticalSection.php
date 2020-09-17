<?php

declare(strict_types=1);

namespace Bileto\CriticalSection;

interface ICriticalSection
{

    /**
     * Enters critical section.
     * @param string $label
     * @return bool
     */
    public function enter(string $label): bool;

    /**
     * Leaves critical section.
     * @param string $label
     * @return bool
     */
    public function leave(string $label): bool;

    /**
     * Returns TRUE if critical section is entered.
     * @param string $label
     * @return bool
     */
    public function isEntered(string $label): bool;

}
