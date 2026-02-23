<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\Polling\PropertySet\FullObjectListPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\FullSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ManagedObjectReferenceSyncStore;

class ManagedObjectReferenceSyncTask extends SyncTask
{
    protected $label = 'Managed Object References';
    protected $tableName = 'object';
    protected $objectClass = ManagedObject::class;
    protected $selectSetClass = FullSelectSet::class;
    protected $propertySetClass = FullObjectListPropertySet::class;
    protected $syncStoreClass = ManagedObjectReferenceSyncStore::class;
}
