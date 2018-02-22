<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Table;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;

class VmsController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('VMs'));
        $this->linkBackToOverview('vm');
        $this->showTable(
            new VmsTable($this->db()),
            'vspheredb/vms',
            $this->translate('Virtual Machines')
        );
    }
}
