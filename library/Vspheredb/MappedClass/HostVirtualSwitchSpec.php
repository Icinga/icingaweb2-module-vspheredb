<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type describes the VirtualSwitch specification representing
 * the properties on a VirtualSwitch that can be configured once the object exists
 */
#[\AllowDynamicProperties]
class HostVirtualSwitchSpec
{
    /**
     * The bridge specification describes how physical network adapters can be
     * bridged to a virtual switch
     *
     * @var HostVirtualSwitchBridge
     */
    public $bridge;

    /**
     * The maximum transmission unit (MTU) of the virtual switch in bytes
     *
     * @var int
     */
    public $mtu;

    /**
     * The number of ports that this virtual switch is configured to use.
     * Changing this setting does not take effect until the next reboot. The
     * maximum value is 1024, although other constraints, such as memory limits,
     * may establish a lower effective limit
     *
     * @var int
     */
    public $numPorts;

    /**
     * The virtual switch policy specification. This has a lower precedence than
     * PortGroup. If the policy property is not set and you are creating a virtual
     * switch, then a default policy property setting is used. If the policy
     * property is not set and you are updating a virtual switch, then the policy
     * will be unchanged
     *
     * @var HostNetworkPolicy
     */
    public $policy;
}
