<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\SelectSet\FullSelectSet;

class FullTraversal extends TraversalHelper
{
    public static function fetchAll(Api $api)
    {
        return $api->collectProperties(static::prepareFullSpecSet($api));
    }

    public static function fetchNames(Api $api)
    {
        return $api->collectProperties(static::prepareNameSpecSet($api));
    }

    protected static function prepareNameSpecSet(Api $api)
    {
        $types = array(
            'Datacenter',
            'Datastore',
            'Folder',
            'ResourcePool',
            'HostSystem',
            'ComputeResource',
            'ClusterComputeResource',
            'StoragePod',
            'VirtualMachine',
        );
        $pathSet = array('name', 'parent', 'overallStatus');

        $propSet = array();
        foreach ($types as $type) {
            $propSet[] = array(
                'type' => $type,
                'all' => 0,
                'pathSet' => $pathSet
            );
        }
        return array(
            'propSet' => $propSet,
            'objectSet' => array(
                'obj' => $api->getServiceInstance()->rootFolder,
                'skip' => false,
                'selectSet' => (new FullSelectSet)->toArray(),
            )
        );
    }

    protected static function prepareFullSpecSet(Api $api)
    {
        return array(
            'propSet' => array(
                array(
                    'type' => 'HostSystem',
                    'all' => 0,
                    'pathSet' => HostSystem::getDefaultPropertySet()
                ),
                array(
                    'type' => 'VirtualMachine',
                    'all' => 0,
                    'pathSet' => VirtualMachine::getDefaultPropertySet()
                ),
                array(
                    'type' => 'Datacenter',
                    'all' => 0,
                    'pathSet' => Datacenter::getDefaultPropertySet()
                ),
                array(
                    'type' => 'Folder',
                    'all' => 0,
                    'pathSet' => Folder::getDefaultPropertySet()
                ),
            ),
            'objectSet' => array(
                'obj' => $api->getServiceInstance()->rootFolder,
                'skip' => false,
                'selectSet' => (new FullSelectSet)->toArray(),
            )
        );
    }
}
