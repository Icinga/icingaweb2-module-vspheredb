<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\ComputeClusterHostSummaryTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\GroupedvmsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;

class ResourcesController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    public function clustersAction()
    {
        $this->addSingleTab('Compute Resources');

        $this->setAutorefreshInterval(15);
        $table = new ComputeClusterHostSummaryTable($this->db());
        if ($vCenterUuid = $this->params->get('vcenter')) {
            $table->filterVCenter(VCenter::load(hex2bin($vCenterUuid), $this->db()));
        }
        /*
        $this->actions()->add(Link::create(
            $this->translate('Chart'),
            '#',
            null,
            ['class' => 'icon-chart-pie']
        ));
        */
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->addTitle($this->translate('Compute Cluster') . ' (%d)', count($table));
        $this->showTable($table, 'vspheredb/groupedvms');
        $table->handleSortUrl($this->url());
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    public function hostsAction()
    {
        $this->addSingleTab('Compute Resources');

        $this->setAutorefreshInterval(15);
        $table = new ComputeClusterHostSummaryTable($this->db());
        if ($vCenterUuid = $this->params->get('vcenter')) {
            $table->filterVCenter(VCenter::load(hex2bin($vCenterUuid), $this->db()));
        }
        if ($uuid = $this->params->get('uuid')) {
            $table->filterParentUuids([hex2bin($uuid)]);
        }
        $this->actions()->add(Link::create(
            $this->translate('Chart'),
            '#',
            null,
            ['class' => 'icon-chart-pie']
        ));
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->addTitle($this->translate('Compute Cluster') . ' (%d)', count($table));
        $this->showTable($table, 'vspheredb/groupedvms');
        $table->handleSortUrl($this->url());
    }

    public function projectsAction()
    {
        $this->addSingleTab('Project Summary');
        $this->setAutorefreshInterval(15);
        $table = new GroupedvmsTable($this->db());
        if ($uuid = $this->params->get('uuid')) {
            $table->filterParentUuids([$uuid]);
        }
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/groupedvms', $this->translate('Projects on this Compute Resource'));
        $table->handleSortUrl($this->url());
    }
}
