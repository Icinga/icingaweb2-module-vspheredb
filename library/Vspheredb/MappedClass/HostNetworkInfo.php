<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class HostNetworkInfo
{
    /** @var boolean */
    public $atBootIpV6Enabled;

    /** @var HostIpRouteConfig */
    public $consoleIpRouteConfig;

    /**
     * Virtual network adapters configured for use by the service console. The
     * service console uses this network access for system management and
     * bootstrapping services like network boot. The two sets of virtual
     * network adapters are mutually exclusive. A virtual network adapter in
     * this list cannot be used for things like VMotion. Likewise, a virtual
     * network adapter in the other list cannot be used by the service console.
     *
     * @var HostVirtualNic[]
     */
    public $consoleVnic;

    /**
     * DHCP Service instances configured on the host
     *
     * @var HostDhcpService[]
     */
    public $dhcp;

    /**
     * Deprecated. As of vSphere API 5.5, which is moved to each NetStackInstance.
     *
     * For this property in NetworkInfo, Get operation will only return its value
     * of default NetStackInstance.
     *
     * @var HostDnsConfig
     */
    public $dnsConfig;

    /**
     * Deprecated. As of vSphere API 5.5, which is moved to each NetStackInstance.
     *
     * For this property in NetworkInfo, Get operation will only return its value
     * of default NetStackInstance.
     *
     * IP route configuration.
     *
     * @var HostIpRouteConfig
     */
    public $ipRouteConfig;

    /** @var boolean */
    public $ipV6Enabled;

    /**
     * NAT service instances configured on the host
     *
     * @var HostNatService[]
     */
    public $nat;

    /**
     *
     * List of NetStackInstances
     *
     * @var HostNetStackInstance[]
     */
    public $netStackInstance;

    /**
     * List of opaque networks
     *
     * @var HostOpaqueNetworkInfo[]
     */
    public $opaqueNetwork;

    /**
     *  List of opaque switches configured on the host
     *
     * @var HostOpaqueSwitch[]
     */
    public $opaqueSwitch;

    /**
     * Physical network adapters as seen by the primary operating system.
     *
     * @var PhysicalNic[]
     */
    public $pnic;

    /**
     * Port groups configured on the host.
     *
     * @var HostPortGroup[]
     */
    public $portgroup;

    /**
     *  Proxy switches configured on the host.
     *
     * @var HostProxySwitch[]
     */
    public $proxySwitch;

    /**
     * Deprecated. As of vSphere API 5.5, which is moved to each NetStackInstance.
     * For this property in NetworkInfo, Get operation will only return its value
     * of default NetStackInstance.
     *
     * IP routing table
     *
     * @var HostIpRouteTableInfo
     */
    public $routeTableInfo;

    /**
     * Virtual network adapters configured on the host (hosted products) or the
     * vmkernel. In the hosted architecture, these network adapters are used by
     * the host to communicate with the virtual machines running on that host.
     *
     * In the VMkernel architecture, these virtual network adapters provide the
     * ESX Server with external network access through a virtual switch that is
     * bridged to a physical network adapter. The VMkernel uses these network
     * adapters for features such as VMotion, NAS, iSCSI, and remote MKS connections.
     *
     * @var HostVirtualNic[]
     */
    public $vnic;

    /**
     * Virtual switches configured on the host
     *
     * @var HostVirtualSwitch[]
     */
    public $vswitch;
}
