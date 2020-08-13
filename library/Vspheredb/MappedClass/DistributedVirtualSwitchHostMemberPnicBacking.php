<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The DistributedVirtualSwitchHostMemberPnicBacking data object specifies a
 * set of physical NICs to use for a proxy switch. When you add a host to a
 * distributed virtual switch (DistributedVirtualSwitchHostMemberConfigSpec.host),
 * the host creates a proxy switch that will use the pNICs as uplinks.
 */
class DistributedVirtualSwitchHostMemberPnicBacking extends DistributedVirtualSwitchHostMemberBacking
{
    /**
     * List of physical NIC specifications. Each entry identifies a pNIC to
     * the proxy switch and optionally specifies uplink portgroup and port
     * connections for the pNIC
     *
     * @var DistributedVirtualSwitchHostMemberPnicSpec[]
     */
    public $pnicSpec;
}
