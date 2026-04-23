<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class VirtualMachineSelectSet implements SelectSet
{
    public const TRAVERSE_VIRTUAL_APP = 'TraverseVirtualApp';

    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                self::TRAVERSE_VIRTUAL_APP,
                GenericSpec::TRAVERSE_DC_VIRTUAL_MACHINES,
            ]),
            GenericSpec::traverseDatacenterVirtualMachines(),
            GenericSpec::traverse(self::TRAVERSE_VIRTUAL_APP, 'VirtualApp', 'vm'),
        ];
    }
}
