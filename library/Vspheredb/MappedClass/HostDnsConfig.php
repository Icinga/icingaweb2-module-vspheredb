<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type describes the DNS configuration
 *
 * All IPv4 addresses, subnet addresses, and netmasks are specified using dotted
 * decimal notation. For example, "192.0.2.1". IPv6 addresses are 128-bit
 * addresses represented as eight fields of up to four hexadecimal digits.
 *
 * A colon separates each field (:). For example, 2001:DB8:101::230:6eff:fe04:d9ff.
 * The address can also consist of the symbol '::' to represent multiple 16-bit
 * groups of contiguous 0's only once in an address as described in RFC 2373.
 */
#[\AllowDynamicProperties]
class HostDnsConfig
{
    /**
     * The IP addresses of the DNS servers, placed in order of preference
     *
     * Note: When DHCP is not enabled, the property can be set explicitly. When
     * DHCP is enabled, the property reflects the current DNS configuration, but
     * cannot be set.
     *
     * @var string[]
     */
    public $address;

    /**
     * The flag to indicate whether or not DHCP (dynamic host control protocol)
     * is used to determine DNS configuration automatically.
     *
     * @var boolean
     */
    public $dhcp;

    /**
     * The domain name portion of the DNS name. For example, "vmware.com"
     *
     * Note: When DHCP is not enabled, the property can be set explicitly. When
     * DHCP is enabled, the property reflects the current DNS configuration, but
     * cannot be set.
     *
     * @var string
     */
    public $domainName;

    /**
     * The host name portion of DNS name. For example, "esx01"
     *
     * Note: When DHCP is not enabled, the property can be set explicitly. When
     * DHCP is enabled, the property reflects the current DNS configuration, but
     * cannot be set.
     *
     * @var string
     */
    public $hostName;

    /**
     * The domain in which to search for hosts, placed in order of preference
     *
     * Note: When DHCP is not enabled, the property can be set explicitly. When
     * DHCP is enabled, the property reflects the current DNS configuration, but
     * cannot be set.
     *
     * @var string[]
     */
    public $searchDomain;

    /**
     * If DHCP is enabled, the DHCP DNS of the service console network adapter will
     * override the system DNS. This field is ignored if DHCP is disabled by the dhcp
     * property.
     *
     * @var string
     */
    public $virtualNicDevice;
}
