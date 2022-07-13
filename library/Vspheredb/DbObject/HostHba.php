<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostHba extends BaseVmHardwareDbObject
{
    protected $keyName = ['host_uuid', 'hba_key'];

    protected $table = 'host_hba';

    protected $defaultProperties = [
        'host_uuid'       => null,
        'hba_key'         => null,
        'device'          => null,
        'bus'             => null,
        'driver'          => null,
        'model'           => null,
        'pci'             => null,
        'status'          => null,
        'storage_protocol' => 'scsi',
        'vcenter_uuid'    => null,
    ];

    protected $propertyMap = [
        'device'          => 'device',
        'bus'             => 'bus',
        'driver'          => 'driver',
        'model'           => 'model',
        'pci'             => 'pci',
        'status'          => 'status',
        'storageProtocol' => 'storage_protocol',
    ];
}
