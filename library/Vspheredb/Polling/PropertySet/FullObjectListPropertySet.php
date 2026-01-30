<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class FullObjectListPropertySet implements PropertySet
{
    public static function create(): array
    {
        return static::propertySet([
            'Datacenter',
            'Datastore',
            'Folder',
            'ResourcePool',
            'HostSystem',
            'ComputeResource',
            'ClusterComputeResource',
            'StoragePod',
            'VirtualMachine',
            'VirtualApp',
            'Network',
            'DistributedVirtualSwitch',
            'DistributedVirtualPortgroup',
        ], ['name', 'parent', 'overallStatus', 'tag']);
    }

    /**
     * @param string[] $types
     * @param ?string[] $pathSet
     *
     * @return PropertySpec[]
     */
    public static function propertySet(array $types, ?array $pathSet = null): array
    {
        $propSet = [];
        foreach ($types as $type) {
            $propSet[] = PropertySpec::create($type, $pathSet, false);
        }

        return $propSet;
    }
}
