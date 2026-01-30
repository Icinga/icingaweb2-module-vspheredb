<?php

namespace Icinga\Module\Vspheredb\Hook;

abstract class AnonymizerHook
{
    /**
     * @param string|null $string
     *
     * @return string|null
     */
    abstract public function anonymizeString(?string $string): ?string;

    /**
     * @param string|null $string
     *
     * @return string|null
     */
    abstract public function shuffleString(?string $string): ?string;
}
