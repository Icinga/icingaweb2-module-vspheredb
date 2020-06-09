<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

class VirtualMachineSelectSet extends SelectSet
{
    public function toArray()
    {
        return [
            static::traverseFolder(),
            static::traverseDatacenter(),
            static::traverseVApp(),
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
            static::makeSelectionSet('TraverseVirtualApp'),
            static::makeSelectionSet('TraverseDatacenter')
        );

        return new SoapVar($folderTraversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    public static function traverseVApp()
    {
        $folderTraversalSpec = array(
            'name' => 'TraverseVirtualApp',
            'type' => 'VirtualApp',
            'path' => 'vm',
            'skip' => false,
        );

        return new SoapVar($folderTraversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    public static function traverseDatacenter()
    {
        $traversalSpec = array(
            'name' => 'TraverseDatacenter',
            'type' => 'Datacenter',
            'path' => 'vmFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($traversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }
}
