<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use SoapVar;

class HostSystem extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',
            'parent',
            'configStatus',
            'overallStatus',
            'datastore',
            'vm',
            'hardware.biosInfo.biosVersion',
            'hardware.biosInfo.releaseDate',
            'hardware.systemInfo.model',
            'hardware.systemInfo.vendor',
            'runtime.powerState',
            'summary.hardware.cpuModel',
            'summary.hardware.cpuMhz',
            'summary.hardware.memorySize',
        );
    }

    public static function getType()
    {
        return 'HostSystem';
    }

    public static function objectSet($base)
    {
        return array(
            'obj'   => $base,
            'skip'  => false,
            'selectSet' => array(
                static::traverseFolder(),
                static::traverseDatacenter(),
            ),
        );
    }

    public static function traverseFolder()
    {
        $folderTraversalSpec = array(
            'name' => 'TraverseFolder',
            'type' => 'Folder',
            'path' => 'childEntity',
            'skip' => false,
            TraversalHelper::makeSelectionSet('TraverseFolder'),
            TraversalHelper::makeSelectionSet('TraverseDatacenter'),
            TraversalHelper::makeSelectionSet('TraverseComputeResource')
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
            TraversalHelper::makeSelectionSet('TraverseFolder')
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
