<?php

namespace Icinga\Module\Vspheredb\Hook;

abstract class AnonymizerHook
{
    /**
     * @param ?string $string
     *
     * @return ?string
     */
    abstract public function anonymizeString(?string $string): ?string;

    /**
     * @param ?string $string
     *
     * @return ?string
     */
    abstract public function shuffleString(?string $string): ?string;
}
