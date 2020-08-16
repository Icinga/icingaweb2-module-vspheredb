<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\HostPciDevicesTable;
use Icinga\Module\Vspheredb\Web\Table\HostSensorsTable;
use Icinga\Module\Vspheredb\Web\Table\Object\HostHardwareInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\HostSystemInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\HostVirtualizationInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Object\HostVmsInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\CustomValueDetails;
use Icinga\Module\Vspheredb\Web\Widget\HostHeader;
use Icinga\Module\Vspheredb\Web\Widget\HostMonitoringInfo;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class HostController extends Controller
{
    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $host = $this->addHost();
        $this->content()->addAttributes(['class' => 'host-info']);
        $monitoring = new HostMonitoringInfo($host);
        if ($monitoring->hasInfo()) {
            $this->addSubTitle($this->translate('Monitoring'), 'binoculars');
            $this->content()->add($monitoring);
        }
        $this->addSubTitle($this->translate('Virtual Machines'), 'cubes');
        $this->content()->add(new HostVmsInfoTable($host));
        $this->content()->add(new CustomValueDetails($host));
        $this->addSubTitle($this->translate('Hardware Information'), 'help');
        $this->content()->add(new HostHardwareInfoTable($host));
        $this->addSubTitle($this->translate('System Information'), 'host');
        $this->content()->add(new HostSystemInfoTable($host));
        $this->addSubTitle($this->translate('Virtualization Information'), 'cloud');
        $this->content()->add(new HostVirtualizationInfoTable($host));
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function vmsAction()
    {
        $host = $this->addHost();
        $table = new VmsTable($this->db(), $this->url());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());

        $table->filterHost($host->get('uuid'))->renderTo($this);
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function sensorsAction()
    {
        $table = new HostSensorsTable($this->db());
        $table->filterHost($this->addHost());
        $table->renderTo($this);
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function pcidevicesAction()
    {
        $table = new HostPciDevicesTable($this->db());
        $table->filterHost($this->addHost())->renderTo($this);
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function eventsAction()
    {
        $table = new EventHistoryTable($this->db());
        $table->filterHost($this->addHost())->renderTo($this);
    }

    /**
     * @return HostSystem
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function addHost()
    {
        $host = HostSystem::load(hex2bin($this->params->getRequired('uuid')), $this->db());
        $this->controls()->add(new HostHeader($host));
        $this->setTitle($host->object()->get('object_name'));
        $this->handleTabs($host);

        return $host;
    }

    /**
     * @param HostSystem $host
     * @throws \Icinga\Exception\MissingParameterException
     */
    protected function handleTabs(HostSystem $host)
    {
        $hexId = $this->params->getRequired('uuid');
        $this->tabs()->add('index', [
            'label' => $this->translate('Host System'),
            'url' => 'vspheredb/host',
            'urlParams' => ['uuid' => $hexId]
        ])->add('vms', [
            'label' => sprintf(
                $this->translate('Virtual Machines (%d)'),
                $host->countVms()
            ),
            'url' => 'vspheredb/host/vms',
            'urlParams' => ['uuid' => $hexId]
        ])->add('sensors', [
            'label' => $this->translate('Sensors'),
            'url' => 'vspheredb/host/sensors',
            'urlParams' => ['uuid' => $hexId]
        ])->add('pcidevices', [
            'label' => $this->translate('PCI Devices'),
            'url' => 'vspheredb/host/pcidevices',
            'urlParams' => ['uuid' => $hexId]
        ])->add('events', [
            'label' => $this->translate('Events'),
            'url' => 'vspheredb/host/events',
            'urlParams' => ['uuid' => $hexId]
        ])->activate($this->getRequest()->getActionName());
    }
}
