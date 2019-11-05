<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class AlarmEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $alarm;
}
