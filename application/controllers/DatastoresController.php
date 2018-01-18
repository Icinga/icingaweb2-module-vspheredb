<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\DatastoreTable;

class DatastoresController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Datatores'));
        $this->linkBackToOverview('datastore');
        $this->showTable(
            new DatastoreTable($this->db()),
            'vspheredb/datastores',
            $this->translate('Datastores')
        );
    }
}
