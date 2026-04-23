<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastoreDiskPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'DatastoreDisk';
    protected $objectType = 'Datastore';
    protected $countersGroup = 'disk';
    protected $counters = [
        'capacity',
        'used',
        'provisioned',
        'deltaused',
    ];
}
