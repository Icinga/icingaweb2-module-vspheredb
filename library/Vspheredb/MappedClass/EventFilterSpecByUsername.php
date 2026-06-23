<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This option specifies users used to filter event history
 *
 * #[AllowDynamicProperties]
 */
class EventFilterSpecByUsername
{
    /**
     * Filter by system user true for system user event
     *
     * @var boolean
     */
    public $systemUser;

    /**
     * All interested username list If this property is not set, then all
     * regular user events are collected
     *
     * @var ?string[]
     */
    public $userList;
}
