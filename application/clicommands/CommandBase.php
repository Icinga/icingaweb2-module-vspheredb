<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Cli\Command;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;

class CommandBase extends Command
{
    /** @var VCenter */
    private $vCenter;

    /** @var VCenterServer */
    private $vCenterServer;

    /** @var Api */
    private $api;

    /** @var Db */
    private $db;

    protected function getVCenter()
    {
        if ($this->vCenter === null) {
            $this->vCenter = VCenter::loadWithAutoIncId(
                $this->params->getRequired('vCenter'),
                $this->db()
            );
        }

        return $this->vCenter;
    }

    protected function getVCenterServer()
    {
        if ($this->vCenterServer === null) {
            $this->vCenterServer = VCenterServer::loadWithAutoIncId(
                $this->params->getRequired('server_id'),
                $this->db()
            );
        }

        return $this->vCenterServer;
    }

    protected function api()
    {
        if ($this->api === null) {
            Benchmark::measure('Preparing the API');
            $this->api = Api::forServer($this->getVCenterServer());
            $this->api->login();
            Benchmark::measure('Logged in, ready to fetch');
        }

        return $this->api;
    }

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::newConfiguredInstance();
        }

        return $this->db;
    }
}
