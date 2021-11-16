<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\SyncStore\VmEventHistorySyncStore;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Psr\Log\LoggerInterface;

class VmEventHistorySyncTask extends SyncTask implements StandaloneTask
{
    protected $label = 'Events';
    protected $tableName = 'vm_event_history';
    protected $syncStoreClass = VmEventHistorySyncStore::class;

    public function run(VsphereApi $api, LoggerInterface $logger)
    {
        return $api->readNextEvents();
    }
}
