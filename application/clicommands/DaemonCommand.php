<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\MainRunner;

/**
 * Sync a vCenter or ESXi host
 */
class DaemonCommand extends CommandBase
{
    /**
     * Sync all objects
     *
     * Still a prototype
     *
     * USAGE
     *
     * icingacli vsphere daemon run --vCenter <id>
     */
    public function runAction()
    {
        $runner = new MainRunner($this->params->getRequired('vCenterId'));
        $runner->run();
    }
}
