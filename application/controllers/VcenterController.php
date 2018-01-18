<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Object\VCenterInfoTable;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSummaries;

class VcenterController extends Controller
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('vCenter Overview'));
        $vcenter = VCenter::loadWithAutoIncId(1, $this->db());
        $this->content()->add([
            new VCenterInfoTable($vcenter),
            new VCenterSummaries($vcenter),
        ]);
    }
}
