<?php

namespace Icinga\Module\Vsphere\ManagedObject;

class HostMountInfo extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'accessMode',         // (string) "readWrite"
            'accessible',         // (bool) true
            'inaccessibleReason', // (string) Unset
            'mounted',            // (bool) true
            'path',               // (string) /vmfs/volumes/..
        );
    }
}
