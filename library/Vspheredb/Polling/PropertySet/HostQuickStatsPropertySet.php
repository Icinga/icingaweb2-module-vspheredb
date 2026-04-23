<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class HostQuickStatsPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('HostSystem', [
                'summary.quickStats.distributedCpuFairness',
                'summary.quickStats.distributedMemoryFairness',
                'summary.quickStats.overallCpuUsage',
                'summary.quickStats.overallMemoryUsage',
                'summary.quickStats.uptime',
            ])
        ];
    }
}
