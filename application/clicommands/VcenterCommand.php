<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Clicommands;

class VcenterCommand extends Command
{
    /**
     * Deprecated
     */
    public function initializeAction()
    {
        $this->fail('Command has been deprecated, please check our documentation');
    }
}
