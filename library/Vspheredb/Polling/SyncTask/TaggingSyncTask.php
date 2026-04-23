<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\SyncStore\TaggingSyncStore;

abstract class TaggingSyncTask extends SyncTask implements RestApiTask
{
    protected $syncStoreClass = TaggingSyncStore::class;
}
