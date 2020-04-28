<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Application\Cli;
use Icinga\Cli\Command;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;

class CommandBase extends Command
{
    /** @var VCenter */
    private $vCenter;

    public function init()
    {
        $this->app->getModuleManager()->loadEnabledModules();
        $this->clearProxySettings();
    }

    protected function clearProxySettings()
    {
        $settings = [
            'http_proxy',
            'https_proxy',
            'HTTPS_PROXY',
            'ALL_PROXY',
        ];
        foreach ($settings as $setting) {
            putenv("$setting=");
        }
    }

    protected function getVCenter()
    {
        if ($this->vCenter === null) {
            $this->vCenter = VCenter::loadWithAutoIncId(
                $this->requiredParam('vCenter'),
                Db::newConfiguredInstance()
            );
        }

        return $this->vCenter;
    }

    public function fail($msg)
    {
        echo $this->screen->colorize("$msg\n", 'red');
        exit(1);
    }

    protected function requiredParam($name)
    {
        $value = $this->params->get($name);
        if ($value === null) {
            /** @var Cli $app */
            $app = $this->app;
            $this->showUsage($app->cliLoader()->getActionName());
            $this->fail("'$name' parameter is required");
        }

        return $value;
    }
}
