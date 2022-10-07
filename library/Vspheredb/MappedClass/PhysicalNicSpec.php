<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class PhysicalNicSpec
{
    /**
     * The IP configuration on the physical network adapter (applies only to a
     * hosted network adapter). The data object will be NULL on an ESX Server
     * system.
     *
     * @var HostIpConfig
     */
    public $ip;

    /**
     * The link speed and duplexity that this physical network adapter is currently
     * configured to use. If this property is not set, the physical network adapter
     * autonegotiates its proper settings.
     *
     * @var PhysicalNicLinkInfo
     */
    public $linkSpeed;
}
