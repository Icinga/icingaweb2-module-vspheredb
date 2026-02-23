<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastorePerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'Datastore';
    protected $objectType = 'Datastore';
    protected $countersGroup = 'datastore';
    protected $counters = [
        'read',
        'write',
        'datastoreReadBytes',
        'datastoreWriteBytes',
        'datastoreReadIops',
        'datastoreWriteIops',
        'totalReadLatency',
        'totalWriteLatency',
    ];
}
