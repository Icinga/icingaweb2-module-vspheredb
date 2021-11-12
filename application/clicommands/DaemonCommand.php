<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\SimpleDaemon\Daemon;
use Icinga\Module\Vspheredb\Daemon\VsphereDbDaemon;

class DaemonCommand extends Command
{
    /**
     * vSphereDB background daemon
     *
     * USAGE
     *
     * icingacli vsphere daemon run [--verbose|--debug]
     */
    public function runAction()
    {
        $this->assertNoVcenterParam();
        $daemon = new Daemon();
        $daemon->setLogger($this->logger);
        $daemon->attachTask(new VsphereDbDaemon());
        $daemon->run($this->loop());
        $this->eventuallyStartMainLoop();
    }

    protected function assertNoVcenterParam()
    {
        if ($this->params->get('vCenterId')) {
            $this->fail(
                'The parameter --vCenterId has been deprecated with v1.0.0,'
                . ' please check our documentation'
            );
        }
    }
}
