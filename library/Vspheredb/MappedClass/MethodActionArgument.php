<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 *  This data object type defines a named argument for an operation
 */
class MethodActionArgument extends DynamicData
{
    /** @var mixed anyType - The value of the argument */
    public $value;
}
