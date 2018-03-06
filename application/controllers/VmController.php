<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmDatastoresTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmLiveCountersTable;
use Icinga\Module\Vspheredb\Web\Table\VmDiskUsageTable;
use Icinga\Module\Vspheredb\Web\Table\VMotionHistoryTable;
use Icinga\Module\Vspheredb\Web\Widget\VmHardwareTree;

class VmController extends Controller
{
    public function indexAction()
    {
        $vm = $this->addVm();
        $this->content()->add([
            new VmInfoTable($vm, $this->vCenter(), $this->pathLookup()),
            VmDatastoresTable::create($vm)
        ]);

        $disks = VmDiskUsageTable::create($vm);
        if (count($disks)) {
            $this->content()->add($disks);
        }
    }

    public function hardwareAction()
    {
        $vm = $this->addVm();
        $this->content()->add(new VmHardwareTree($vm));
    }

    public function vmotionsAction()
    {
        $table = new VMotionHistoryTable($this->db());
        $table->filterVm($this->addVm())->renderTo($this);
    }

    public function countersAction()
    {
        $vm = $this->addVm();
        $api = Api::forServer(
            // TODO: remove hardcoded id=1
            VCenterServer::loadWithAutoIncId(1, $this->db())
        )->login();

        $this->setAutorefreshInterval(10);
        $this->content()->add(new VmLiveCountersTable($vm, $api));
    }

    protected function addVm()
    {
        $vm = VirtualMachine::load(hex2bin($this->params->getRequired('uuid')), $this->db());
        $this->addTitle($vm->object()->get('object_name'));
        $this->handleTabs();

        return $vm;
    }

    protected function handleTabs()
    {
        $params = ['uuid' => $this->params->get('uuid')];
        $this->tabs()->add('index', [
            'label'     => $this->translate('Virtual Machine'),
            'url'       => 'vspheredb/vm',
            'urlParams' => $params
        ])->add('hardware', [
            'label'     => $this->translate('Hardware'),
            'url'       => 'vspheredb/vm/hardware',
            'urlParams' => $params
        ])->add('vmotions', [
            'label'     => $this->translate('VMotions'),
            'url'       => 'vspheredb/vm/vmotions',
            'urlParams' => $params
        ])->add('counters', [
            'label'     => $this->translate('Live Counters'),
            'url'       => 'vspheredb/vm/counters',
            'urlParams' => $params
        ])->activate($this->getRequest()->getActionName());
    }
}
