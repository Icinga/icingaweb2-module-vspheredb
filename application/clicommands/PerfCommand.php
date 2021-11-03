<?php

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
