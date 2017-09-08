<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

use Icinga\Module\Vspheredb\SelectSet\FullSelectSet;

class Datastore extends ManagedObject
{
    public static function getType()
    {
        return 'Datastore';
    }

    public static function getDefaultPropertySet()
    {
        return array(
            'host',          // DatastoreHostMount[]
            'info',          // DataStoreInfo
            'summary',       // DatastoreSummary
            'overallStatus', // ManagedEntityStatus "green"
            'parent',        // ManagedObjectReference:Folder
            'vm',            // ManagedObjectReference:VirtualMachine[]
        );
    }

    public static function objectSet($base)
    {
        return array(
                'obj' => $base,
                'skip' => false,
                'selectSet' => (new FullSelectSet)->toArray(),
        );
    }
}
