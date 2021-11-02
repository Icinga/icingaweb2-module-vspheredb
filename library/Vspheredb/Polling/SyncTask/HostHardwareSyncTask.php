<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostPciDevice;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostHardwarePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostHardwareSyncStore;

class HostHardwareSyncTask extends SyncTask
{
    protected $label = 'Host Hardware';
    protected $tableName = 'host_pci_device';
    protected $objectClass = HostPciDevice::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostHardwarePropertySet::class;
    protected $syncStoreClass = HostHardwareSyncStore::class;
}
