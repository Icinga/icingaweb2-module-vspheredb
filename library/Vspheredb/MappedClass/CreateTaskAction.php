<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type specifies the type of task to be created when this
 * action is triggered
 */
class CreateTaskAction extends Action
{
    /** @var bool Whether the task should be cancelable */
    public $cancelable;

    /** @var string Extension registered task type identifier for type of task being created */
    public $taskTypeId;
}
