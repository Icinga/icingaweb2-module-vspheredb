<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\SyncStore\VmEventHistorySyncStore;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

class VmEventHistorySyncTask extends SyncTask implements StandaloneTask
{
    protected string $label = 'Events';

    protected string $tableName = 'vm_event_history';

    protected string $syncStoreClass = VmEventHistorySyncStore::class;

    public function run(VsphereApi $api, LoggerInterface $logger): PromiseInterface
    {
        return $api->readNextEvents();
    }
}
