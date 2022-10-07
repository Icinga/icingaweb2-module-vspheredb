<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class HostNetStackInstance
{
    /**
     * The TCP congest control algorithm used by this instance, See
     * CongestionControlAlgorithmType for valid values.
     *
     * @var string
     */
    public $congestionControlAlgorithm;

    /**
     * DNS configuration
     *
     * @var HostDnsConfig
     */
    public $dnsConfig;

    /**
     * IP Route configuration
     *
     * @var HostIpRouteConfig
     */
    public $ipRouteConfig;

    /**
     * Enable or disable IPv6 protocol on this stack instance. This property is
     * not supported currently
     *
     * @var boolean
     */
    public $ipV6Enabled;

    /**
     * Key of instance For instance which created by host, its value shoud be
     * SystemStackKey
     *
     * @var string
     */
    public $key;

    /**
     * The display name
     *
     * @var string
     */
    public $name;

    /**
     * The maximum number of socket connection that are requested on this instance
     *
     * @var int
     */
    public $requestedMaxNumberOfConnections;

    /**
     * @var HostIpRouteTableConfig
     */
    public $routeTableConfig;
}
