<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class DynamicData
{
    /** @var DynamicProperty[]|null */
    public $dynamicProperty;

    /** @var string|null */
    public $dynamicType;
}
