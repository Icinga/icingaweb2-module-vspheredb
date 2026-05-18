<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostQuickStatsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class HostQuickStatsSyncTask extends SyncTask
{
    protected string $label = 'Host Quick Stats';

    protected string $tableName = 'host_quick_stats';

    protected string $objectClass = HostQuickStats::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostQuickStatsPropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
