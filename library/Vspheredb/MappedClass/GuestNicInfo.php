<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Information about each virtual network adapter configured in the guest operating system
 */
class GuestNicInfo
{
    /** @var bool Flag indicating whether the virtual device is connected */
    public $connected;

    /** @var int Link to the corresponding virtual device */
    public $deviceConfigId;

    /**
     * DNS configuration of the adapter. This property is set only when Guest OS supports it.
     * See StackInfo dnsConfig for system-wide settings
     *
     * @var NetDnsConfigInfo
     */
    public $dnsConfig;

    /** @var array string[] Deprecated. as of vSphere API 5.0, use ipConfig property */
    public $ipAddress = [];

    /**
     * IP configuration settings of the adapter See StackInfo ipStackConfig for system wide settings
     *
     * @var NetIpConfigInfo
     */
    public $ipConfig;

    /** @var string MAC address of the adapter */
    public $macAddress;

    /** @var NetBIOSConfigInfo NetBIOS configuration of the adapter */
    public $netBIOSConfig;

    /** @var string Name of the virtual switch portgroup or dvPort connected to this adapter */
    public $network;
}
