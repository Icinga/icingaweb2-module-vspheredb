<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type is used to describe port groups. Port groups are used
 * to group virtual network adapters on a virtual switch, associating them with
 * networks and network policies
 *
 * #[AllowDynamicProperties]
 */
class HostPortGroup
{
    /**
     * Computed network policies that are applicable for a port group. The
     * inheritance scheme for PortGroup requires knowledge about the NetworkPolicy
     * for a port group and its parent virtual switch as well as the logic for
     * computing the results. This information is provided as a convenience so
     * that callers need not duplicate the inheritance logic to determine the
     * proper values for a network policy.
     *
     * See the description of the NetworkPolicy data object type for more information.
     *
     * @var HostNetworkPolicy
     */
    public $computedPolicy;

    /**
     * The linkable identifier
     *
     * @var string
     */
    public $key;

    /**
     * The ports that currently exist and are used on this port group
     *
     * @var HostPortGroupPort[]
     */
    public $port;

    /**
     * The specification of a port group
     *
     * @var HostPortGroupSpec
     */
    public $spec;

    /**
     * The virtual switch that contains this port group
     *
     * @var string
     */
    public $vswitch;
}
