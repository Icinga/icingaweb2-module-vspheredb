<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class VmEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $vm;
}
