<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostVirtualNic extends BaseVmHardwareDbObject
{
    protected $keyName = ['host_uuid', 'nic_key'];

    protected $table = 'host_virtual_nic';

    protected $defaultProperties = [
        'host_uuid'              => null,
        'nic_key'                => null,
        'net_stack_instance_key' => null,
        'port'                   => null,
        'portgroup'              => null,
        'mac_address'            => null,
        'mtu'                    => null,
        'ipv4_address'           => null,
        'ipv4_subnet_mask'       => null,
        // TODO: What about IPv6?
        'ipv6_address'           => null,
        'ipv6_prefic_length'     => null,
        'ipv6_dad_state'         => null,
        'ipv6_origin'            => null,
        'dv_connection_cookie'   => null,
        'dv_portgroup_key'       => null,
        'dv_port_key'            => null,
        'dv_switch_uuid'         => null,
        // opaqueNetwork?
        // pinnedPnic?
        'device'                 => null,
        'tso_enabled'            => null,
        'vcenter_uuid'           => null,
    ];

    protected $propertyMap = [
        'device'                      => 'device',
        'port'                        => 'port',
        'portgroup'                   => 'portgroup',
        'spec.distributedVirtualPort.connectionCookie' => 'dv_connection_cookie',
        'spec.distributedVirtualPort.portgroupKey'     => 'dv_portgroup_key',
        'spec.distributedVirtualPort.portKey'          => 'dv_port_key',
        'spec.distributedVirtualPort.switchUuid'       => 'dv_switch_uuid',
        'spec.distributedVirtualPort.ip.ipAddress'     => 'ipv4_address',
        'spec.distributedVirtualPort.ip.subnetMask'    => 'ipv4_subnet_mask',
        /*
         -> nope, ipV6Config is an array
        'spec.distributedVirtualPort.ip.ipV6Config.ipAddress'     => 'ipv6_address',
        'spec.distributedVirtualPort.ip.ipV6Config.prefixLength'     => 'ipv6_prefic_length',
        'spec.distributedVirtualPort.ip.ipV6Config.dadState'     => 'ipv6_dad_state',
        'spec.distributedVirtualPort.ip.ipV6Config.origin'     => 'ipv6_origin',
        */
        'spec.distributedVirtualPort.mac'        => 'mac_address',
        'spec.distributedVirtualPort.tsoEnabled' => 'tso_enabled',
    ];
}
