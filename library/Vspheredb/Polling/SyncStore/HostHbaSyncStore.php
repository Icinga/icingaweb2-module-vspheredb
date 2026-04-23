<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHbaSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.storageDevice.hostBusAdapter';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'hba_key';
    protected $instanceClass = 'HostHostBusAdapter';
}
