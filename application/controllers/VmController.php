<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Addon\BackupTool;
use Icinga\Module\Vspheredb\Addon\IbmSpectrumProtect;
use Icinga\Module\Vspheredb\Addon\VeeamBackup;
use Icinga\Module\Vspheredb\Addon\VRangerBackup;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\AlarmHistoryTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmEssentialInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\VmExtraInfoTable;
use Icinga\Module\Vspheredb\Web\Table\VmDatastoresTable;
use Icinga\Module\Vspheredb\Web\Table\VmDisksTable;
use Icinga\Module\Vspheredb\Web\Table\VmDiskUsageTable;
use Icinga\Module\Vspheredb\Web\Table\VmNetworkAdapterTable;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Table\VmSnapshotTable;
use Icinga\Module\Vspheredb\Web\Widget\CustomValueDetails;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Module\Vspheredb\Web\Widget\VmHardwareTree;
use Icinga\Module\Vspheredb\Web\Widget\VmHeader;
use ipl\Html\Html;

class VmController extends Controller
{
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
        $this->content()->add([
            new SubTitle($this->translate('Information'), 'info-circled'),
            new VmEssentialInfoTable($vm),
            new CustomValueDetails($vm),
            new VmNetworkAdapterTable($vm),
            VmDisksTable::create($vm),
            new SubTitle($this->translate('DataStore Usage'), 'database'),
            VmDatastoresTable::create($vm),
            new SubTitle($this->translate('Snapshots'), 'history'),
        ]);

        $snapshots = VmSnapshotTable::create($vm);
        if (count($snapshots)) {
            $this->content()->add($snapshots);
        } else {
            $this->content()->add(Html::tag('p', null, $this->translate('No snapshots have been created for this VM')));
        }

        $this->content()->add(new SubTitle($this->translate('Backup-Tools'), 'download'));
        $tools = $this->getBackupTools();
        $seenBackupTools = 0;
        foreach ($tools as $tool) {
            if ($tool->wants($vm)) {
                $seenBackupTools++;
                $tool->handle($vm);
                $this->content()->add(Html::tag('h3', null, $tool->getName()));
                $this->content()->add($tool->getInfoRenderer());
            }
        }
        if ($seenBackupTools === 0) {
            $this->content()->add(Html::tag(
                'p',
                null,
                $this->translate('No known backup tool has been used for this VM')
            ));
        }

        $this->content()->add(new SubTitle($this->translate('Guest Disk Usage'), 'chart-pie'));
        $disks = VmDiskUsageTable::create($vm);
        if (count($disks)) {
            $this->content()->add($disks);
        }

        $this->content()->add([
            new SubTitle($this->translate('Additional Information'), 'info-circled'),
            new VmExtraInfoTable($vm),
        ]);
    }

    /**
     * TODO: Use a hook once the API stabilized
     * @return BackupTool[]
     */
    protected function getBackupTools()
    {
        return [
            new IbmSpectrumProtect(),
            new VeeamBackup(),
            new VRangerBackup(),
        ];
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
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     * @deprecated
     */
    public function countersActionObsolete()
    {
        $vm = $this->addVm();

        $vCenter = VCenter::load($vm->get('vcenter_uuid'), $this->db());
        $api = $vCenter->getApi();
        $moRef = $vm->object()->get('moref');
        $interval = 20;
        $raw = $api->perfManager()->oldTestQueryPerf(
            $moRef,
            'VirtualMachine',
            $interval,
            600
        );

        // $this->content()->add(new VmLiveCountersTable($vm, $api));
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
        /** // Disabled for now
        ->add('counters', [
            'label'     => $this->translate('Live Counters'),
            'url'       => 'vspheredb/vm/counters',
            'urlParams' => $params
        ])
        **/
        ->activate($this->getRequest()->getActionName());
    }
}
