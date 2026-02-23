<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostCpuPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'HostCpu';
    protected $objectType = 'HostSystem';
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
