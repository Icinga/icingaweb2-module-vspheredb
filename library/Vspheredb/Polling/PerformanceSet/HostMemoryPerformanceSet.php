<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'HostMemory';
    protected $objectType = 'HostSystem';
    protected $countersGroup = 'mem';
    protected $counters = [
        'active',
        'usage',
        'totalCapacity',
        'latency',
        'swapin',
        'swapout',
        'swapinRate',
        'swapoutRate',
        'vmmemctl',
    ];
}
