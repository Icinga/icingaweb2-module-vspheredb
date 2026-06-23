<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

class SystemError extends Fault
{
    public $reason;

    public function getMessage()
    {
        return $this->reason;
    }
}
