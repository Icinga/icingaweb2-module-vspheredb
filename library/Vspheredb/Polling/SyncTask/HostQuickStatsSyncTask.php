<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostQuickStatsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class HostQuickStatsSyncTask extends SyncTask
{
    protected $label = 'Host Quick Stats';
    protected $tableName = 'host_quick_stats';
    protected $objectClass = HostQuickStats::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostQuickStatsPropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
