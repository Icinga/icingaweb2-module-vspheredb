<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

use Icinga\Module\Vspheredb\MappedClass\SelectionSpec;
use Icinga\Module\Vspheredb\MappedClass\TraversalSpec;

abstract class GenericSpec
{
    public const TRAVERSE_FOLDER = 'TraverseFolder';
    public const TRAVERSE_DC_HOST_SYSTEMS = 'DatacenterHosts';
    public const TRAVERSE_DC_VIRTUAL_MACHINES = 'DatacenterVirtualMachines';
    public const TRAVERSE_DC_DATA_STORES = 'DatacenterDataStores';
    public const TRAVERSE_DC_NETWORKS = 'DatacenterNetworks';

    /**
     * @param string[] $specReferences
     *
     * @return TraversalSpec
     */
    public static function traverseFolder(array $specReferences = []): TraversalSpec
    {
        return self::traverse(self::TRAVERSE_FOLDER, 'Folder', 'childEntity', array_merge([
            self::TRAVERSE_FOLDER
        ], $specReferences));
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $path
     *
     * @return TraversalSpec
     */
    public static function traverseDatacenter(string $name, string $type, string $path): TraversalSpec
    {
        return self::traverse($name, $type, $path, [
            self::TRAVERSE_FOLDER
        ]);
    }

    public static function traverseDatacenterHosts(): TraversalSpec
    {
        return self::traverseDatacenter(self::TRAVERSE_DC_HOST_SYSTEMS, 'Datacenter', 'hostFolder');
    }

    public static function traverseDatacenterVirtualMachines(): TraversalSpec
    {
        return self::traverseDatacenter(self::TRAVERSE_DC_VIRTUAL_MACHINES, 'Datacenter', 'vmFolder');
    }

    public static function traverseDatacenterDataStores(): TraversalSpec
    {
        return self::traverseDatacenter(self::TRAVERSE_DC_DATA_STORES, 'Datacenter', 'datastoreFolder');
    }

    public static function traverseDatacenterNetworks(): TraversalSpec
    {
        return self::traverseDatacenter(self::TRAVERSE_DC_NETWORKS, 'Datacenter', 'networkFolder');
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $path
     * @param ?SelectionSpec[]|string[] $selectionSet
     *
     * @return TraversalSpec
     */
    public static function traverse(
        string $name,
        string $type,
        string $path,
        ?array $selectionSet = null
    ): TraversalSpec {
        if ($selectionSet) {
            foreach ($selectionSet as $key => $entry) {
                if (is_string($entry)) {
                    $selectionSet[$key] = SelectionSpec::reference($entry);
                } elseif (! $entry instanceof SelectionSpec) {
                    throw new \InvalidArgumentException('string of SelectionSpec expected, got ' . gettype($entry));
                }
            }
        }

        return TraversalSpec::create($name, $type, $path, $selectionSet, false);
    }
}
