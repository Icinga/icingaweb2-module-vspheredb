<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class HostEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $host;
}
