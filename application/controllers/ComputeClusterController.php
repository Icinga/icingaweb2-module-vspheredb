<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\ComputeCluster;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\ComputeClusterHeader;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class ComputeClusterController extends Controller
{
    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $computeCluster = $this->addComputeCluster();
        $this->content()->addAttributes(['class' => 'host-info']);
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function hostsAction()
    {
        $computeCluster = $this->addComputeCluster();
        $table = new HostsTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());

        $table->handleSortUrl($this->url())
            ->filterParentUuids([$computeCluster->get('uuid')])
            ->renderTo($this);
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    /**
     * @return ComputeCluster
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function addComputeCluster()
    {
        $computeCluster = ComputeCluster::load(hex2bin($this->params->getRequired('uuid')), $this->db());
        $this->controls()->add(new ComputeClusterHeader($computeCluster));
        $this->setTitle($computeCluster->get('object_name'));
        $this->handleTabs($computeCluster);

        return $computeCluster;
    }

    /**
     * @param ComputeCluster $computeCluster
     * @throws \Icinga\Exception\MissingParameterException
     */
    protected function handleTabs(ComputeCluster $computeCluster)
    {
        $hexId = $this->params->getRequired('uuid');
        $this->tabs()->add('index', [
            'label' => $this->translate('Compute Cluster'),
            'url' => 'vspheredb/compute-cluster',
            'urlParams' => ['uuid' => $hexId]
        ])->add('hosts', [
            'label' => sprintf(
                $this->translate('Host Systems (%d)'),
                $computeCluster->countHosts()
            ),
            'url' => 'vspheredb/compute-cluster/hosts',
            'urlParams' => ['uuid' => $hexId]
        ])->activate($this->getRequest()->getActionName());
    }
}
