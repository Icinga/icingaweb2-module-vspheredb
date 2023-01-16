<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The virtual switch is a software entity to which multiple virtual network
 * adapters can connect to create a virtual network. It can also be bridged to
 * a physical network
 */
#[\AllowDynamicProperties]
class HostVirtualSwitch
{
    /**
     * The virtual switch key
     *
     * @var string
     */
    public $key;

    /**
     * The maximum transmission unit (MTU) associated with this virtual switch
     * in bytes
     *
     * @var int
     */
    public $mtu;

    /**
     * The name of the virtual switch. Maximum length is 32 characters
     *
     * @var string
     */
    public $name;

    /**
     * The number of ports that this virtual switch currently has
     *
     * @var int
     */
    public $numPorts;

    /**
     * The number of ports that are available on this virtual switch. There are
     * a number of networking services that utilize a port on the virtual switch
     * and are not accounted for in the Port array of a PortGroup. For example,
     * each physical NIC attached to a virtual switch consumes one port. This
     * property should be used when attempting to implement admission control
     * for new services attaching to virtual switches
     *
     * @var
     */
    public $numPortsAvailable;

    /**
     * The set of physical network adapters associated with this bridge
     *
     * @var string[]
     */
    public $pnic;

    /**
     * The list of port groups configured for this virtual switch
     *
     * @var string[]
     */
    public $portgroup;

    /**
     * The specification of a PortGroup
     *
     * @var HostVirtualSwitchSpec
     */
    public $spec;
}
