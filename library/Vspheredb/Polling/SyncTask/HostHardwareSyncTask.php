<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostPciDevice;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostHardwarePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostHardwareSyncStore;

class HostHardwareSyncTask extends SyncTask
{
    protected string $label = 'Host Hardware';

    protected string $tableName = 'host_pci_device';

    protected string $objectClass = HostPciDevice::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostHardwarePropertySet::class;

    protected string $syncStoreClass = HostHardwareSyncStore::class;
}
