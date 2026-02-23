<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * This data object type defines the action initiated by a scheduled task or
 * alarm
 *
 * This is an abstract type. A client creates a scheduled task or an alarm each
 * of which triggers an action, defined by a subclass of this type
 *
 * Hint: Not defining as abstract here to have a fallback for upcoming new actions
 * we don't know about
 */
class Action extends DynamicData
{
}
