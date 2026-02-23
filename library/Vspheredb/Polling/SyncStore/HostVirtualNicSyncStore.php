<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostVirtualNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.network.vnic';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'nic_key';
    protected $instanceClass = 'HostVirtualNic';
}
