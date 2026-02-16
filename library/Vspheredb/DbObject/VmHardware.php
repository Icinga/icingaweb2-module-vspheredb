<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VmHardware extends BaseVmHardwareDbObject
{
    protected ?string $table = 'vm_hardware';

    protected ?array $defaultProperties = [
        'vm_uuid'        => null,
        'hardware_key'   => null,
        'bus_number'     => null,
        'unit_number'    => null,
        'controller_key' => null,
        'label'          => null,
        'summary'        => null,
        'vcenter_uuid'   => null
    ];

    protected array $objectReferences = [
        'vm_uuid'
    ];

    protected array $propertyMap = [
        'deviceInfo.label'   => 'label',
        'deviceInfo.summary' => 'summary',
        'busNumber'          => 'bus_number',
        'unitNumber'         => 'unit_number',
        'controllerKey'      => 'controller_key'
    ];
}
