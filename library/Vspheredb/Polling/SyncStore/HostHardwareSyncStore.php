<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHardwareSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'hardware.pciDevice';
    protected $keyProperty = 'id';
    protected $dbKeyProperty = 'id';
    protected $instanceClass = 'HostPciDevice';
}
