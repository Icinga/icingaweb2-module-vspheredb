<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

class HostSystemSelectSet extends SelectSet
{
    public function toArray()
    {
        return [
            static::traverseFolder(),
            static::traverseDatacenter(),
            static::traverseComputeResource(),
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
            static::makeSelectionSet('TraverseComputeResource')
        );

        return new SoapVar($folderTraversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    public static function traverseDatacenter()
    {
        $traversalSpec = array(
            'name' => 'TraverseDatacenter',
            'type' => 'Datacenter',
            'path' => 'hostFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($traversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseComputeResource()
    {
        $spec = array(
            'name' => 'TraverseComputeResource',
            'type' => 'ComputeResource',
            'path' => 'host',
            'skip' => false
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }
}
