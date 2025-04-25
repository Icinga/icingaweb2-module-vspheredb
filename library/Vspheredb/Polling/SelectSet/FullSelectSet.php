<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

use Icinga\Module\Vspheredb\MappedClass\SelectionSpec;

class FullSelectSet implements SelectSet
{
    public const TRAVERSE_CR1 = 'TraverseCR1';
    public const TRAVERSE_CR2 = 'TraverseCR2';
    public const TRAVERSE_RP1 = 'TraverseRP1';
    public const TRAVERSE_RP2 = 'TraverseRP2';
    public const TRAVERSE_STORAGE_POD = 'TraverseStoragePod';

    /**
     * @return SelectionSpec[]
     */
    public static function create()
    {
        return [
            GenericSpec::traverseDatacenterHosts(),
            GenericSpec::traverseDatacenterVirtualMachines(),
            GenericSpec::traverseDatacenterDataStores(),
            GenericSpec::traverseDatacenterNetworks(),
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_NETWORKS,
                GenericSpec::TRAVERSE_DC_DATA_STORES,
                GenericSpec::TRAVERSE_DC_HOST_SYSTEMS,
                GenericSpec::TRAVERSE_DC_VIRTUAL_MACHINES,
                self::TRAVERSE_CR1,
                self::TRAVERSE_CR2,
                self::TRAVERSE_STORAGE_POD,
            ]),
            GenericSpec::traverse(self::TRAVERSE_STORAGE_POD, 'StoragePod', 'childEntity'),
            GenericSpec::traverse(self::TRAVERSE_CR1, 'ComputeResource', 'resourcePool', [
                // A ComputeResource object can be followed either by a ResourcePool
                // or a HostSystem. There is no need to traverse a HostSystem, but
                // there are two different ResourcePool TraversalSpecs to cover, so
                // TraverseCR1 needs an array of two SelectionSpec objects, named
                // TraverseRP1 and TraverseRP2
                SelectionSpec::reference(self::TRAVERSE_RP1),
                SelectionSpec::reference(self::TRAVERSE_RP2),
            ]),
            // TraverseCR2 can lead only to a HostSystem object, so there is no
            // need for it to have a selectSet array
            GenericSpec::traverse(self::TRAVERSE_CR2, 'ComputeResource', 'host'),
            GenericSpec::traverse(self::TRAVERSE_RP1, 'ResourcePool', 'resourcePool', [
                // TraverseRP1 can lead only to another ResourcePool, but there are
                // two paths out of ResourcePool, so it needs an array of two
                // SelectionSpec objects, named TraverseRP1 and TraverseRP2
                SelectionSpec::reference(self::TRAVERSE_RP1),
                SelectionSpec::reference(self::TRAVERSE_RP2),
            ]),
            GenericSpec::traverse(self::TRAVERSE_RP2, 'ResourcePool', 'vm'),
        ];
    }
}
