<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

class GlobalMessageChangedEvent extends SessionEvent
{
    /** @var string The new message that was set */
    public $message;

    /** @var string|null The previous message that was set */
    public $prevMessage;
}
