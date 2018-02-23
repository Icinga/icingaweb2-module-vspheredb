<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsBiosTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;

class HostsController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Hosts'));
        $this->linkBackToOverview('host');
        $table = new HostsTable($this->db());

        $this->showTable($table, 'vspheredb/hosts', $this->translate('Hosts'));
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
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
