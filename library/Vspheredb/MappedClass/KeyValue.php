<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Non-localized key/value pair
 */
#[\AllowDynamicProperties]
class KeyValue
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;
}
