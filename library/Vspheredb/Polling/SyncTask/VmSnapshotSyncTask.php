<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmSnapshot;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmSnapshotPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmSnapshotSyncStore;

class VmSnapshotSyncTask extends SyncTask
{
    protected $label = 'VM Snapshots';
    protected $tableName = 'vm_snapshot';
    protected $objectClass = VmSnapshot::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmSnapshotPropertySet::class;
    protected $syncStoreClass = VmSnapshotSyncStore::class;
}
