<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class DistributedVirtualSwitchSelectSet implements SelectSet
{
    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_NETWORKS
            ]),
            GenericSpec::traverseDatacenterNetworks(),
        ];
    }
}
