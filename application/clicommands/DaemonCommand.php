<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\SimpleDaemon\Daemon;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceProcess;
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
        $this->assertRequiredExtensionsAreLoaded();
        $this->assertNoVcenterParam();
        $daemon = new Daemon();
        $daemon->setLogger($this->logger);
        $vSphereDb = new VsphereDbDaemon();
        $vSphereDb->on(RpcNamespaceProcess::ON_RESTART, function () use ($daemon) {
            $daemon->reload();
        });
        $daemon->attachTask($vSphereDb);
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

    protected function assertRequiredExtensionsAreLoaded()
    {
        $required = ['soap', 'posix', 'pcntl'];
        $missing = [];
        foreach ($required as $extension) {
            if (! extension_loaded($extension)) {
                $missing[] = "php-$extension";
            }
        }

        if (! empty($missing)) {
            $this->fail('Cannot run because of missing dependencies: ' . implode(', ', $missing));
        }
    }
}
