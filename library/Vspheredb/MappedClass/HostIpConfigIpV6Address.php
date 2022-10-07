<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class HostIpConfigIpV6Address
{
    /**
     * The state of this ipAddress. Can be one of HostIpConfigIpV6AddressStatus:
     *
     * - deprecated   : Indicates that this is a valid but deprecated address that
     *                  should no longer be used as a source address.
     * - duplicate    : Indicates the address has been determined to be non-unique
     *                  on the link, this address will not be reachable.
     * - inaccessible : Indicates that the address is not accessible because
     *                  interface is not operational.
     * - invalid      : Indicates that this isn't a valid.
     * - preferred    : Indicates that this is a valid address.
     * - tentative    : Indicates that the uniqueness of the address on the link
     *                  is presently being verified.
     * - unknown      : Indicates that the status cannot be determined.

     *
     * @var string
     */
    public $dadState;

    /**
     * The ipv6 address. When DHCP is enabled, this property reflects the
     * current IP configuration and cannot be set
     *
     * @var string|null
     */
    public $ipAddress;

    /**
     * The time when will this address expire. If not set the address lifetime
     * is unlimited
     *
     * @var string xsd:dateTime
     */
    public $lifetime;


    /**
     * Valid values are "add" and "remove". See HostConfigChangeOperation.
     *
     * @var string
     */
    public $operation;

    /**
     * The type of the ipv6 address configuration on the interface. This can be
     * one of the types defined my the enum HostIpConfigIpV6AddressConfigType:
     *
     * - dhcp      : The address is configured through dhcp
     * - linklayer : The address is obtained through stateless autoconfiguration
     * - manual    : The address is configured manually
     * - other     : Any other type of address configuration other than the below
     *               mentioned ones will fall under this category. For e.g.,
     *               automatic address configuration for the link local address
     *               falls under this type
     * - random    : The address is chosen by the system at random e.g., an IPv4
     *               address within 169.254/16, or an RFC 3041 privacy address
     *
     * @var string
     */
    public $origin;

    /**
     * The prefix length. An ipv6 prefixLength is a decimal value that indicates
     * the number of contiguous, higher-order bits of the address that make up
     * the network portion of the address. For example, 10FA:6604:8136:6502::/64
     * is a possible IPv6 prefix. The prefix length in this case is 64.
     *
     * @var int
     */
    public $prefixLength;
}
