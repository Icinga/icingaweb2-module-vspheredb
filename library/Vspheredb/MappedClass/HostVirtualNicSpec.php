<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class HostVirtualNicSpec
{
    /**
     * DistributedVirtualPort or DistributedVirtualPortgroup connection.
     *
     * To specify a port connection, set the portKey property. To specify a
     * portgroup connection, set the portgroupKey property.
     *
     * @var DistributedVirtualSwitchPortConnection
     */
    public $distributedVirtualPort;


    /**
     * An ID assigned to the vmkernel adapter by external management plane or
     * controller. The value and format of this property is determined by
     * external management plane or controller, and vSphere doesn't do any
     * validation. It's also up to external management plane or controller to
     * set, unset or maintain this property. Setting this property with an
     * empty string value will unset the property.
     *
     * This property is applicable only when opaqueNetwork field is set,
     * otherwise it's value is ignored.
     *
     * Since vSphere API 6.0
     *
     * @var string
     */
    public $externalId;

    /**
     * IP configuration on the virtual network adapter
     *
     * @var HostIpConfig
     */
    public $ip;

    /**
     * Media access control (MAC) address of the virtual network adapter
     *
     * @var string
     */
    public $mac;

    /**
     * Maximum transmission unit for packets size in bytes for the virtual NIC.
     *
     * This property is applicable to VMkernel virtual NICs and will be ignored if
     * specified for service console virtual NICs. If not specified, the Server
     * will use the system default value.
     *
     * @var int
     */
    public $mtu;

    /**
     * The NetStackInstance that the vNic uses, the value of this property is
     * default to be defaultTcpipStack
     *
     *
     * @var string
     */
    public $netStackInstanceKey;

    /**
     * The opaque network that the vNic uses.
     *
     * When reconfiguring a virtual NIC, this property indicates the specification
     * of opaque network to which the virtual NIC should connect. You can specify
     * this property only if you do not specify distributedVirtualPort and portgroup
     *
     * Since vSphere API 6.0
     *
     * @var HostVirtualNicOpaqueNetworkSpec
     */
    public $opaqueNetwork;

    /**
     * The physical nic to which the vmkernel adapter is pinned. Setting this
     * value basically pins the vmkernel adpater to a specific physical nic.
     *
     * Similar to externalId, this property is applicable only when opaqueNetwork
     * field is set. If the vmkernel adapter is connected to a portgroup or dvPort,
     * then this pinning can be achieved by configuring correct teaming policy on
     * the vSwitch or DVSwitch.
     *
     * Therefore, pinnedPnic value will be ignored if opaqueNetwork is unset.
     *
     * Since vSphere API 6.0
     *
     * @var string
     */
    public $pinnedPnic;

    /**
     * Portgroup (HostPortGroup) to which the virtual NIC is connected.
     *
     * When reconfiguring a virtual NIC, this property indicates the new portgroup
     * to which the virtual NIC should connect. You can specify this property only
     * if you do not specify distributedVirtualPort.
     *
     * @var string
     */
    public $portgroup;

    /**
     * Flag enabling or disabling TCP segmentation offset for a virtual NIC.
     *
     * This property is applicable to VMkernel virtual NICs and will be ignored if
     * specified for service console vitual nics. If not specified, a default
     * value of true shall be used.
     *
     * @var boolean
     */
    public $tsoEnabled;
}
