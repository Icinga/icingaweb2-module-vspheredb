<?php

namespace Icinga\Module\Vsphere\ManagedObject;

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
