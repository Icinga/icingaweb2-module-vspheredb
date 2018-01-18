<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsInFolderTable;

class VmsController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('VMs'));
        $this->linkBackToOverview('vm');
        $this->showTable(
            new VmsInFolderTable($this->db()),
            'vspheredb/vms',
            $this->translate('Virtual Machines')
        );
    }
}
