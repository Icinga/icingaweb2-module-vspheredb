<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class DatacenterEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $datacenter;
}
