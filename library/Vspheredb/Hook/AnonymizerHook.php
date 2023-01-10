<?php

namespace Icinga\Module\Vspheredb\Hook;

abstract class AnonymizerHook
{
    abstract public function anonymizeString(?string $string): ?string;

    abstract public function shuffleString(?string $string): ?string;
}
