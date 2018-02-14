<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VmNetworkAdapter extends BaseVmHardwareDbObject
{
    protected $table = 'vm_network_adapter';

    protected $defaultProperties = [
        'vm_uuid'        => null,
        'hardware_key'   => null,
        'portgroup_uuid' => null,
        'port_key'       => null,
        'mac_address'    => null,
        'address_type'   => null,
        'vcenter_uuid'   => null,
    ];

    protected $objectReferences = [
        'portgroup_uuid',
    ];

    protected $propertyMap = [
        'backing.port.portgroupKey' => 'portgroup_uuid',
        'backing.port.portKey'      => 'port_key',
        'macAddress'                => 'mac_address', // binary(6)? new xxeuid?
        'addressType'               => 'address_type',
    ];
}
