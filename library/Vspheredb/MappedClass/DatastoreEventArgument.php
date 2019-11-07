<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class DatastoreEventArgument extends EntityEventArgument
{
    /** @var ManagedObjectReference */
    public $datastore;
}
