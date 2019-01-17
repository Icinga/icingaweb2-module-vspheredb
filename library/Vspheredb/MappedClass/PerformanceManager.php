<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerformanceManager
{
    /** @var PerformanceDescription */
    public $description;

    /** @var PerfInterval[] */
    public $historicalInterval;

    /** @var PerfCounterInfo[] */
    public $perfCounter;
}
