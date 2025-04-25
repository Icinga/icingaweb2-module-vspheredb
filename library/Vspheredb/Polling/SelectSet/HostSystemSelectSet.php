<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

use Icinga\Module\Vspheredb\MappedClass\TraversalSpec;

class HostSystemSelectSet implements SelectSet
{
    public const TRAVERSE_COMPUTE_RESOURCES = 'TraverseComputeResource';

    /**
     * @return TraversalSpec[]
     */
    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_HOST_SYSTEMS,
                self::TRAVERSE_COMPUTE_RESOURCES,
            ]),
            GenericSpec::traverseDatacenterHosts(),
            GenericSpec::traverse(self::TRAVERSE_COMPUTE_RESOURCES, 'ComputeResource', 'host'),
        ];
    }
}
