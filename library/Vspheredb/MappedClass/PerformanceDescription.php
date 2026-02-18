<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class PerformanceDescription
{
    /** @var ElementDescription[] */
    public $counterType;

    /** @var ElementDescription[] */
    public $statsType;
}
