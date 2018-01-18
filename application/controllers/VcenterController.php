<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\Vcenter;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Object\VcenterInfoTable;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSummaries;

class VcenterController extends Controller
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('vCenter Overview'));
        $vcenter = Vcenter::loadWithAutoIncId(1, $this->db());
        $this->content()->add([
            new VcenterInfoTable($vcenter),
            new VCenterSummaries($vcenter),
        ]);
    }
}
