<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;

class CommandBase extends Command
{
    /** @var VCenter */
    private $vCenter;

    protected function getVCenter()
    {
        if ($this->vCenter === null) {
            $this->vCenter = VCenter::loadWithAutoIncId(
                $this->params->getRequired('vCenter'),
                Db::newConfiguredInstance()
            );
        }

        return $this->vCenter;
    }
}
