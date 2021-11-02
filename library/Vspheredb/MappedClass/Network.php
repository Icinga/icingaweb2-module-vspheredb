<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class Network extends ManagedEntity
{
    /**
     * Hosts attached to this network.
     *
     * @var ManagedObjectReference[] to a HostSystem[]
     */
    public $host;

    /**
     * Name of this network.
     *
     * @var string
     */
    public $name;

    /**
     * Properties of a Network
     *
     * @var NetworkSummary
     */
    public $summary;

    /**
     * Virtual machines using this network.
     *
     * @var ManagedObjectReference[] to a VirtualMachine[]
     */
    public $vm;
}
