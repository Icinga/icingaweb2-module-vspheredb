<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Exception;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Polling\ServerSet;

trait RpcServerUpdateHelper
{
    protected function sendServerInfoToSocket(): string
    {
        /** @var ConfigurationController $this */
        try {
            $connection = $this->db();
            if (
                $this->syncRpcCall('vsphere.setServers', [
                'servers' => ServerSet::fromServers(VCenterServer::loadEnabledServers($connection))
                ])
            ) {
                return $this->translate('Daemon configuration has been refreshed');
            }

            return $this->translate('Daemon configuration has NOT been refreshed');
        } catch (Exception $e) {
            return $this->translate('Daemon configuration refresh FAILED: ' . $e->getMessage());
        }
    }
}
