<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class HostIpConfig
{
    /**
     * The flag to indicate whether or not DHCP (dynamic host control protocol)
     * is enabled. If this property is set to true, the ipAddress and the
     * subnetMask strings cannot be set explicitly
     *
     * @var boolean
     */
    public $dhcp;

    /**
     * The IP address currently used by the network adapter. All IP addresses
     * are specified using IPv4 dot notation. For example, "192.168.0.1". Subnet
     * addresses and netmasks are specified using the same notation.
     *
     * Note: When DHCP is enabled, this property reflects the current IP
     * configuration and cannot be set. When DHCP is not enabled, this property
     * can be set explicitly.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * The ipv6 configuration
     *
     * @var HostIpConfigIpV6AddressConfiguration
     */
    public $ipV6Config;

    /**
     * The subnet mask
     *
     * Note: When DHCP is not enabled, this property can be set explicitly. When
     * DHCP is enabled, this property reflects the current IP configuration and
     * cannot be set
     *
     * @var string
     */
    public $subnetMask;
}
