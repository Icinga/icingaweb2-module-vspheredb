<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class HostHostBusAdapter extends DynamicData
{
    /** @var int The host bus number */
    public $bus;

    /** @var string The device name of host bus adapter */
    public $device;

    /** @var ?string The name of the driver */
    public $driver;

    /** @var ?string The linkable identifier */
    public $key;

    /** @var string The model name of the host bus adapter */
    public $model;

    /** @var ?string The Peripheral Connect Interface (PCI) ID of the device representing the host bus adapter */
    public $pci;

    /** @var string The operational status of the adapter. Valid values include "online", "offline", "unbound", and "unknown" */
    public $status;

    /**
     * The type of protocol supported by the host bus adapter.
     *
     * enum of type HostStorageProtocol
     *
     * - nvme
     * - scsi
     *
     * When unset, a default value of "scsi" is assumed.
     *
     * @var ?string
     */
    public $storageProtocol;
}
