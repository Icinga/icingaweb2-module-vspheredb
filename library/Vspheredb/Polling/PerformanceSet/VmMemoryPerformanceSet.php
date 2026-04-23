<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VmMemory';
    protected $objectType = 'VirtualMachine';
    protected $countersGroup = 'mem';
    protected $counters = [
        'active',
        'usage',
        'granted',
        'latency',
        'swapin',
        'swapout',
        'swapinRate',
        'swapoutRate',
        'vmmemctl',
    ];
}
