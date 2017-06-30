<?php

namespace Icinga\Module\Vsphere\ManagedObject;

class Datacenter extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',               // string
            'parent',             // ManagedObjectReference:ManagedEntity (rootFolder -> Unset
            'datastore',          // ManagedObjectReference:Datastore[]
            // 'datastoreFolder', // ManagedObjectReference:Folder
            // 'hostFolder',      // ManagedObjectReference:Folder
            'network',            // ManagedObjectReference:Network[]
            // 'networkFolder',   // ManagedObjectReference:Folder
            // 'vmFolder',        // ManagedObjectReference:Folder
        );
    }
}
