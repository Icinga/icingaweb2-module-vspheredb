<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerformanceManager
{
    /** @var PerformanceDescription */
    public $description;

    /** @var PerfInterval[]|null */
    public $historicalInterval;

    /** @var PerfCounterInfo[] */
    public $perfCounter;
}
