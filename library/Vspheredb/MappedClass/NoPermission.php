<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class NoPermission extends SecurityError
{
    /** @var ManagedObjectReference */
    public $object;

    public $privilegeId;
}
