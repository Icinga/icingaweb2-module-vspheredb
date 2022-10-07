<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The OpaqueSwitch contains basic information about virtual switches that are
 * managed by a management plane outside of vSphere.
 *
 * #[AllowDynamicProperties]
 */
class HostOpaqueSwitch
{
    /**
     * The opaque switch ID
     *
     * @var string
     */
    public $key;

    /**
     * The opaque switch name
     *
     * @var $name
     */
    public $name;

    /**
     * The set of physical network adapters associated with this switch
     *
     * @var string[]
     */
    public $pnic;

    /**
     * The IDs of networking zones associated with this switch.
     *
     * Since vSphere API 6.0
     *
     * @var HostOpaqueSwitchPhysicalNicZone[]
     */
    public $pnicZone;

    /**
     * Opaque switch status. See OpaqueSwitchState for valid values
     *
     * Since vSphere API 6.0
     *
     * @var string
     */
    public $status;

    /**
     * List of VTEPs associated with this switch
     *
     * Since vSphere API 6.0
     *
     * @var HostVirtualNic[]
     */
    public $vtep;
}
