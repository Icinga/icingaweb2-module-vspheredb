<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

class DatastoreHostMount extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'key',       // ManagedObjectReference:HostSystem
            'mountInfo', // HostMountInfo
        );
    }
}
