<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostPhysicalNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.network.pnic';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'nic_key';
    protected $instanceClass = 'PhysicalNic';
}
