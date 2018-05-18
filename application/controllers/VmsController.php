<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsGuestDiskUsageTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class VmsController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\ProgrammingError
     * @throws \Icinga\Exception\IcingaException
     */
    public function indexAction()
    {
        $this->handleTabs();
        $this->addTreeViewToggle();
        if ($this->params->get('render') === 'tree') {
            $this->addTitle($this->translate('Virtual Machines'));
            $this->content()->add(new OverviewTree($this->db(), 'vm'));

            return;
        }

        $this->setAutorefreshInterval(15);
        $table = new VmsTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/vms', $this->translate('Virtual Machines'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    /**
     * @throws \Icinga\Exception\ProgrammingError
     * @throws \Icinga\Exception\IcingaException
     */
    public function diskusageAction()
    {
        $this->addSingleTab($this->translate('Disk Usage'));
        $table = new VmsGuestDiskUsageTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/vms', $this->translate('Virtual Machine Guest Disks'));
    }
}
