<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class ElementDescription
{
    /** @var string */
    public $key;

    /** @var string */
    public $label;

    /** @var string */
    public $summary;
}
