<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\SyncStore\TaggingSyncStore;

abstract class TaggingSyncTask extends SyncTask implements RestApiTask
{
    protected $syncStoreClass = TaggingSyncStore::class;
}
