<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class ComputeResourcePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('ComputeResource', [
                'summary.effectiveCpu',
                'summary.effectiveMemory',
                'summary.numCpuCores',
                'summary.numCpuThreads',
                'summary.numEffectiveHosts',
                'summary.numHosts',
                // 'summary.overallStatus',
                'summary.totalCpu',
                'summary.totalMemory',
            ])
        ];
    }
}
