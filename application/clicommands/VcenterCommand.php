<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;

/**
 * vCenter
 */
class VcenterCommand extends CommandBase
{
    /**
     * Initialize a vCenter
     *
     * icingacli vsphere sync objects --vCenter <id>
     */
    public function initializeAction()
    {
        $db = Db::newConfiguredInstance();
        $this->app->getModuleManager()->loadEnabledModules();
        $server = VCenterServer::loadWithAutoIncId(
            $this->params->getRequired('serverId'),
            $db
        );

        $vCenter = VCenter::fromApi(
            Api::forServer($server),
            $db
        );

        $vCenter->store();
        $server->set('vcenter_id', $vCenter->get('id'));
        $server->store();
    }
}
