<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\ComputeClusterHostSummaryTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\GroupedvmsTable;
use Icinga\Module\Vspheredb\Web\Tabs\VCenterTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;

class ResourcesController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    public function clustersAction()
    {
        if ($vCenterUuid = $this->params->get('vcenter')) {
            $vCenter = VCenter::loadWithHexUuid($vCenterUuid, $this->db());
            $this->tabs(new VCenterTabs($vCenter))->activate('clusters');
        } else {
            $this->addSingleTab('Compute Resources');
            $vCenter = null;
        }

        $this->setAutorefreshInterval(15);
        $table = new ComputeClusterHostSummaryTable($this->db(), $this->url());
        if ($vCenter) {
            $table->filterVCenter($vCenter);
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
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    public function hostsAction()
    {
        $this->addSingleTab('Compute Resources');

        $this->setAutorefreshInterval(15);
        $table = new ComputeClusterHostSummaryTable($this->db(), $this->url());
        if ($vCenterUuid = $this->params->get('vcenter')) {
            $table->filterVCenter(VCenter::loadWithHexUuid($vCenterUuid, $this->db()));
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
    }

    public function projectsAction()
    {
        $this->addSingleTab('Project Summary');
        $this->setAutorefreshInterval(15);
        $table = new GroupedvmsTable($this->db(), $this->url());
        if ($uuid = $this->params->get('uuid')) {
            $table->filterParentUuids([$uuid]);
        }
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/groupedvms', $this->translate('Projects on this Compute Resource'));
    }
}
