<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Polling\ServerSet;

trait RpcServerUpdateHelper
{
    protected function sendServerInfoToSocket()
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
            } else {
                return $this->translate('Daemon configuration has NOT been refreshed');
            }
        } catch (\Exception $e) {
            return $this->translate('Daemon configuration refresh FAILED: ' . $e->getMessage());
        }
    }
}
