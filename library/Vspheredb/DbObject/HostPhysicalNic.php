<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostPhysicalNic extends BaseVmHardwareDbObject
{
    protected $keyName = ['host_uuid', 'nic_key'];

    protected $table = 'host_physical_nic';

    protected $defaultProperties = [
        'host_uuid'                => null,
        'nic_key'                  => null,
        'auto_negotiate_supported' => null,
        'device'                   => null,
        'driver'                   => null,
        'link_speed_mb'            => null,
        'link_duplex'              => null,
        'mac_address'              => null,
        'pci'                      => null,
        'vcenter_uuid'             => null,
    ];

    protected $propertyMap = [
        'device'                 => 'device',
        'driver'                 => 'driver',
        'pci'                    => 'pci',
        'linkSpeed.speedMb'      => 'link_speed_mb',
        'linkSpeed.duplex'       => 'link_duplex',
        'macAddress'             => 'mac_address',
        'autoNegotiateSupported' => 'auto_negotiate_supported',
    ];

    protected $booleanProperties = [
        'auto_negotiate_supported',
        'link_duplex',
    ];
}
