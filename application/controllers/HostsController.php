<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsBiosTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;

class HostsController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Hosts'));
        $this->linkBackToOverview('host');
        $this->showTable(
            new HostsTable($this->db()),
            'vspheredb/hosts',
            $this->translate('Hosts')
        );
    }

    public function spectreAction()
    {
        $this->addSingleTab($this->translate('Hosts'));
        $this->showTable(
            new HostsBiosTable($this->db()),
            'vspheredb/hosts/spectre',
            $this->translate('Hosts with Spectre/Meltdown hints')
        );
    }
}
