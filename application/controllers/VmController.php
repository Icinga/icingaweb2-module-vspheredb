<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\DbObject\VcenterServer;
use Icinga\Module\Vspheredb\DbObject\VmConfig;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmDatastoresTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmLiveCountersTable;

class VmController extends Controller
{
    public function indexAction()
    {
        $vm = $this->addVm();
        $this->content()->add([
            new VmInfoTable($vm, $this->pathLookup()),
            VmDatastoresTable::create($vm)
        ]);
    }

    public function countersAction()
    {
        $vm = $this->addVm();
        $api = Api::forServer(
            VcenterServer::loadWithAutoIncId(1, $this->db())
        )->login();

        $this->content()->add(new VmLiveCountersTable($vm, $api));
    }

    protected function addVm()
    {
        $vm = VmConfig::load($this->params->getRequired('id'), $this->db());
        $this->addTitle($vm->object()->get('object_name'));
        $this->handleTabs();

        return $vm;
    }

    protected function handleTabs()
    {
        $params = ['id' => $this->params->get('id')];
        $this->tabs()->add('index', [
            'label'     => $this->translate('Virtual Machine'),
            'url'       => 'vspheredb/vm',
            'urlParams' => $params
        ])->add('counters', [
            'label'     => $this->translate('Live Counters'),
            'url'       => 'vspheredb/vm/counters',
            'urlParams' => $params
        ])->activate($this->getRequest()->getActionName());
    }
}
