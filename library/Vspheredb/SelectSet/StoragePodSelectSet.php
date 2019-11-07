<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

class StoragePodSelectSet extends FullSelectSet
{
    public function toArray()
    {
        return [
            static::traverseFolder(),
            static::traverseDatacenter(),
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
}
