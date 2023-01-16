<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The HostProxySwitch is a software entity which represents the component of a
 * DistributedVirtualSwitch on a particular host.
 *
 * A hidden standard switch that resides on every host that is associated with
 * a vSphere distributed switch. The host proxy switch replicates the networking
 * configuration set on the vSphere distributed switch to the particular host.
 */
#[\AllowDynamicProperties]
class HostProxySwitch
{
    /**
     * The configured number of ports that this switch has. If configured number
     * of ports is changed, a host reboot is required for the new value to take
     * effect
     *
     * @var int
     */
    public $configNumPorts;

    /**
     * The name of the DistributedVirtualSwitch that the HostProxySwitch is part of
     * @var string
     */
    public $dvsName;

    /**
     * The uuid of the DistributedVirtualSwitch that the HostProxySwitch is a part of
     *
     * @var string
     */
    public $dvsUuid;

    /**
     * The Link Aggregation Control Protocol group and Uplink ports in the group
     *
     * @var HostProxySwitchHostLagConfig[]
     */
    public $hostLag;

    /**
     * The proxy switch key
     *
     * @var string
     */
    public $key;

    /**
     * The maximum transmission unit (MTU) associated with this switch in bytes
     *
     * @var int
     */
    public $mtu;

    /**
     * Indicates whether network reservation is supported on this switch
     *
     * @var boolean
     */
    public $networkReservationSupported;

    /**
     * The number of ports that this switch currently has
     *
     * @var int
     */
    public $numPorts;

    /**
     * The number of ports that are available on this virtual switch
     *
     * @var int
     */
    public $numPortsAvailable;

    /**
     * The set of physical network adapters associated with this switch
     *
     * @var string[]
     */
    public $pnic;

    /**
     * The specification of the switch
     *
     * @var HostProxySwitchSpec
     */
    public $spec;

    /**
     * The list of ports that can be potentially used by physical nics. This
     * property contains the keys and names of such ports
     *
     * @var KeyValue[]
     */
    public $uplinkPort;
}
