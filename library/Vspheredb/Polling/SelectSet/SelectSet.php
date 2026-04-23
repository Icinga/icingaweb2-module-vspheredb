<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

use Icinga\Module\Vspheredb\MappedClass\SelectionSpec;

interface SelectSet
{
    /**
     * @return SelectionSpec[]
     */
    public static function create();
}
