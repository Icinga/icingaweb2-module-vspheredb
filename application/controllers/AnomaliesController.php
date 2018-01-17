<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmsWithDuplicateBiosUuidTable;

class AnomaliesController extends Controller
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Anomalies'));
        $table = new VmsWithDuplicateBiosUuidTable($this->db());
        if (count($table)) {
            $this->content()->add([
                Html::tag('h1', null, 'Virtual Machines with duplicate SM BIOS UUID'),
                $table
            ]);
        }

        $table = new VmsWithDuplicateBiosUuidTable($this->db());
        $table->setProperty('instance_uuid', $this->translate('Instance UUID'));
        if (count($table)) {
            $this->content()->add([
                Html::tag('h1', null, 'Virtual Machines with duplicate Instance UUID'),
                $table
            ]);
        }

        $table = new VmsWithDuplicateBiosUuidTable($this->db());
        $table->setProperty('guest_host_name', $this->translate('Guest host name'));
        if (count($table)) {
            $this->content()->add([
                Html::tag('h1', null, 'Virtual Machines with duplicate Guest host name'),
                $table
            ]);
        }
    }
}
