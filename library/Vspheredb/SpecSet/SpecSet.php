<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\SpecSet;

abstract class SpecSet
{
    /**
     * @return array
     */
    abstract public function toArray();
}
