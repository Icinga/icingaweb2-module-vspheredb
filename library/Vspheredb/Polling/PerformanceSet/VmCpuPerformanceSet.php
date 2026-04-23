<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmCpuPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VmCpu';
    protected $objectType = 'VirtualMachine';
    protected $countersGroup = 'cpu';
    protected $counters = [
        'coreUtilization',
        'demand',
        'latency',
        'readiness',
        'usage',
        'usagemhz',
        'utilization',
    ];
}
