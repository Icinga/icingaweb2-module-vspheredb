<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class HostVirtualNic
{
    /**
     * Device name
     *
     * @var string
     */
    public $device;

    /**
     * Linkable identifier
     *
     * @var string
     */
    public $key;

    /**
     * Port on the port group that the virtual network adapter is using when it
     * is enabled (port).
     *
     * @var string
     */
    public $port;

    /**
     * If the Virtual NIC is connecting to a vSwitch, this property is the name
     * of portgroup connected. If the Virtual NIC is connecting to a
     * DistributedVirtualSwitch, this property is an empty string.
     *
     * @var string
     */
    public $portgroup;

    /**
     * Configurable properties for the virtual network adapter object
     *
     * @var HostVirtualNicSpec
     */
    public $spec;
}
