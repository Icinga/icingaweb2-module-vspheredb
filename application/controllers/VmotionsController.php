<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Table\VMotionHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;

class VmotionsController extends Controller
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('VMotion History'));
        $table = new VMotionHistoryTable($this->db());
        $table->renderTo($this);
    }
}
