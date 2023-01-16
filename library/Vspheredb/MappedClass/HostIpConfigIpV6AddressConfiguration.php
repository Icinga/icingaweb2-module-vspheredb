<?php

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class HostIpConfigIpV6AddressConfiguration
{
    /**
     * Specify if IPv6 address and routing information information be enabled
     * or not as per RFC 2462.
     *
     * @var boolean
     */
    public $autoConfigurationEnabled;

    /**
     * The flag to indicate whether or not DHCP (dynamic host control protocol)
     * is enabled to obtain an ipV6 address. If this property is set to true,
     * an ipV6 address is configured through dhcpV6.
     *
     * @var boolean
     */
    public $dhcpV6Enabled;

    /**
     * Ipv6 adrresses configured on the interface. The global addresses can be
     * configured through DHCP, stateless or manual configuration. Link local
     * addresses can be only configured with the origin set to other.
     *
     * @var HostIpConfigIpV6Address[]
     */
    public $ipV6Address;
}
