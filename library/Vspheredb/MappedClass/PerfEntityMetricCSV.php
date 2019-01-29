<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class PerfEntityMetricCSV
{
    /** @var ManagedObjectReference */
    public $entity;

    /** @var string */
    public $sampleInfoCSV;

    /** @var PerfMetricSeriesCSV[] */
    public $value = [];
}
