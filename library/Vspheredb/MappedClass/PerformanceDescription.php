<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class PerformanceDescription
{
    /** @var ElementDescription[] */
    public $counterType;

    /** @var ElementDescription[] */
    public $statsType;
}
