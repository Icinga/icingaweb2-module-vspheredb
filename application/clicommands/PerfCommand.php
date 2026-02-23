<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Clicommands;

class PerfCommand extends Command
{
    /**
     * Deprecated. The main daemon now provides this functionality
     */
    public function influxdbAction()
    {
        echo "ERROR: This command is no longer required\n";
        sleep(5);
        exit;
    }
}
