<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Link;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsGuestDiskUsageTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsSnapshotsTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class VmsController extends ObjectsController
{
    protected $otherTabActions = [
        'diskusage' => 'index',
        'snapshot'  => 'index',
    ];

    public function indexAction()
    {
        $this->handleTabs();
        $this->addTreeViewToggle();
        if ($this->params->get('render') === 'tree') {
            $this->addTitle($this->translate('Virtual Machines'));
            $this->content()->add(new OverviewTree($this->db(), 'vm'));

            return;
        }

        $this->actions()->add([
            Link::create(
                $this->translate('Disk Usage'),
                'vspheredb/vms/diskusage',
                null,
                ['class' => 'icon-chart-pie']
            ),
            Link::create(
                $this->translate('Snapshots'),
                'vspheredb/vms/snapshot',
                null,
                ['class' => 'icon-database']
            ),
        ]);

        $this->setAutorefreshInterval(15);
        $table = new VmsTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/vms', $this->translate('Virtual Machines'));
        $table->handleSortUrl($this->url());
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    public function diskusageAction()
    {
        $this->handleTabs();
        $this->actions()->add([
            Link::create(
                $this->translate('Table'),
                'vspheredb/vms',
                null,
                ['class' => 'icon-left-small']
            ),
            Link::create(
                $this->translate('Snapshots'),
                'vspheredb/vms/snapshot',
                null,
                ['class' => 'icon-database']
            ),
        ]);
        $table = new VmsGuestDiskUsageTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/vms', $this->translate('Virtual Machine Guest Disks'));
    }

    public function snapshotAction()
    {
        $this->handleTabs();
        $this->actions()->add([
            Link::create(
                $this->translate('Disk Usage'),
                'vspheredb/vms/diskusage',
                null,
                ['class' => 'icon-chart-pie']
            ),
            Link::create(
                $this->translate('Table'),
                'vspheredb/vms',
                null,
                ['class' => 'icon-left-small']
            ),
        ]);
        $table = new VmsSnapshotsTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/vms', $this->translate('Virtual Machines with Snapshots'));
    }
}
