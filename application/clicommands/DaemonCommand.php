<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\Log\Filter\LogLevelFilter;
use gipfl\SimpleDaemon\Daemon;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceProcess;
use Icinga\Module\Vspheredb\Daemon\VsphereDbDaemon;
use Icinga\Module\Vspheredb\Db;
use Throwable;

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
        $this->eventuallyApplyConfiguredLogLevel();
        $daemon->setLogger($this->logger);
        $vSphereDb = new VsphereDbDaemon();
        $vSphereDb->on(RpcNamespaceProcess::ON_RESTART, function () use ($daemon) {
            $daemon->reload();
        });
        $daemon->attachTask($vSphereDb);
        $daemon->run($this->loop());
        $this->eventuallyStartMainLoop();
    }

    protected function eventuallyApplyConfiguredLogLevel(): void
    {
        // Setting the log level via CLI flags has higher precedence than database config
        if ($this->isVerbose || $this->isDebugging) {
            return;
        }

        $db = null;
        try {
            $db = Db::newConfiguredInstance()->getDbAdapter();
            $level = $db->fetchOne(
                $db->select()
                    ->from('daemon_config', ['value'])
                    ->where('`key` = ?', 'log_level')
            );
            if (! is_string($level) || $level === '') {
                return;
            }

            $newFilter = new LogLevelFilter($level);
            $this->logger->addFilter($newFilter);
            foreach ($this->logger->getFilters() as $filter) {
                if ($filter instanceof LogLevelFilter && $filter !== $newFilter) {
                    $this->logger->removeFilter($filter);
                }
            }
        } catch (Throwable) {
            // Keep current log level if DB config is not available yet.
        } finally {
            $db?->closeConnection();
        }
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
