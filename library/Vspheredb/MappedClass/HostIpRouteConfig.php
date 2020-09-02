<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * IP Route Configuration. All IPv4 addresses, subnet addresses, and netmasks
 * are specified as strings using dotted decimal notation. For example, "192.0.2.1".
 * IPv6 addresses are 128-bit addresses represented as eight fields of up to four
 * hexadecimal digits. A colon separates each field (:). For example,
 * 2001:DB8:101::230:6eff:fe04:d9ff. The address can also consist of symbol '::'
 * to represent multiple 16-bit groups of contiguous 0's only once in an address
 * as described in RFC 2373.
 */
class HostIpRouteConfig
{
    /**
     * The default gateway address
     *
     * @var string
     */
    public $defaultGateway;

    /**
     * The gateway device. This applies to service console gateway only, it is
     * ignored otherwise.
     *
     * @var string
     */
    public $gatewayDevice;

    /**
     * The default ipv6 gateway address
     *
     * @var string
     */
    public $ipV6DefaultGateway;

    /**
     * The ipv6 gateway device. This applies to service console gateway only
     *
     * @var string
     */
    public $ipV6GatewayDevice;
}
