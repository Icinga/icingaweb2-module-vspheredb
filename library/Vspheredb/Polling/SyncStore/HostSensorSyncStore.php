<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostSensorSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'runtime.healthSystemRuntime.systemHealthInfo.numericSensorInfo';
    protected $keyProperty = 'name';
    protected $dbKeyProperty = 'name';
    protected $instanceClass = 'HostNumericSensorInfo';
}
