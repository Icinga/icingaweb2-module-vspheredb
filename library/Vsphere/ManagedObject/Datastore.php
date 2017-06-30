<?php

namespace Icinga\Module\Vsphere\ManagedObject;

class Datastore extends ManagedObject
{
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
}
