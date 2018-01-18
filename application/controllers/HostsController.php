<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
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
}
