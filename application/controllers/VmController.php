<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\AlarmHistoryTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmEssentialInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmExtraInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmLocationInfoTable;
use Icinga\Module\Vspheredb\Web\Table\VmDatastoresTable;
use Icinga\Module\Vspheredb\Web\Table\VmDisksTable;
use Icinga\Module\Vspheredb\Web\Table\VmDiskUsageTable;
use Icinga\Module\Vspheredb\Web\Table\VmNetworkAdapterTable;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Table\VmSnapshotTable;
use Icinga\Module\Vspheredb\Web\Widget\CustomValueDetails;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Module\Vspheredb\Web\Widget\Vm\BackupToolInfo;
use Icinga\Module\Vspheredb\Web\Widget\VmHardwareTree;
use Icinga\Module\Vspheredb\Web\Widget\VmHeader;

class VmController extends Controller
{
    use DetailSections;

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $vm = $this->addVm();
        $this->content()->addAttributes([
            'class' => 'vm-info'
        ]);
        $vCenter = VCenter::load($vm->get('vcenter_uuid'), $vm->getConnection());
        $this->addSections([
            new VmEssentialInfoTable($vm),
            new VmLocationInfoTable($vm, $vCenter),
            new CustomValueDetails($vm),
            new VmNetworkAdapterTable($vm),
            new VmDatastoresTable($vm),
            new VmDisksTable($vm),
            new VmDiskUsageTable($vm),
            new VmSnapshotTable($vm),
            new BackupToolInfo($vm),
            new VmExtraInfoTable($vm),
        ]);
    }

    /**
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function hardwareAction()
    {
        $vm = $this->addVm();
        $this->content()->add([
            new SubTitle($this->translate('Hardware'), 'print'),
            new VmHardwareTree($vm),
        ]);
    }

    /**
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function eventsAction()
    {
        $table = new EventHistoryTable($this->db());
        $table->filterVm($this->addVm())->renderTo($this);
    }

    /**
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function alarmsAction()
    {
        $table = new AlarmHistoryTable($this->db());
        $table->filterEntityUuid($this->addVm()->get('uuid'))->renderTo($this);
    }

    /**
     * @return VirtualMachine
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function addVm()
    {
        /** @var VirtualMachine $vm */
        $vm = VirtualMachine::load(hex2bin($this->params->getRequired('uuid')), $this->db());
        $this->controls()->add(new VmHeader($vm));
        $this->setTitle($vm->object()->get('object_name'));
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
        ])->add('events', [
            'label'     => $this->translate('Events'),
            'url'       => 'vspheredb/vm/events',
            'urlParams' => $params
        ])->add('alarms', [
            'label'     => $this->translate('Alarms'),
            'url'       => 'vspheredb/vm/alarms',
            'urlParams' => $params
        ])
        ->activate($this->getRequest()->getActionName());
    }
}
