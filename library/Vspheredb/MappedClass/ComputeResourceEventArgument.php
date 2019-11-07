<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class ComputeResourceEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $computeResource;
}
