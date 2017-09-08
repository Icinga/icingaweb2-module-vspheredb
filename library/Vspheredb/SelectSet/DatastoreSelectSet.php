<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

class DatastoreSelectSet extends FullSelectSet
{
    public function toArray()
    {
        return [
            static::traverseFolder(),
            static::traverseDatacenter(),
            static::traverseStoragePod(),
        ];
    }

    public static function traverseFolder()
    {
        $folderTraversalSpec = array(
            'name' => 'TraverseFolder',
            'type' => 'Folder',
            'path' => 'childEntity',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder'),
            static::makeSelectionSet('TraverseDatacenter'),
            static::makeSelectionSet('TraverseStoragePod')
        );

        return new SoapVar($folderTraversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    public static function traverseDatacenter()
    {
        $traversalSpec = array(
            'name' => 'TraverseDatacenter',
            'type' => 'Datacenter',
            'path' => 'datastoreFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($traversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseStoragePod()
    {
        $spec = array(
            'name' => 'TraverseStoragePod',
            'type' => 'StoragePod',
            'path' => 'childEntity',
            'skip' => false
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }
}
