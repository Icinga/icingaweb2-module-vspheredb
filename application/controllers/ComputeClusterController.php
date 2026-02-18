<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Exception\MissingParameterException;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\ComputeCluster;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\ComputeClusterHeader;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class ComputeClusterController extends Controller
{
    /**
     * @throws MissingParameterException
     * @throws NotFoundError
     */
    public function indexAction(): void
    {
        $computeCluster = $this->addComputeCluster();
        $this->content()->addAttributes(['class' => 'host-info']);
        $table = new HostsTable($this->db(), $this->url());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());

        $table->filterParentUuids([$computeCluster->get('uuid')])
            ->renderTo($this);
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    /**
     * @return ComputeCluster
     *
     * @throws MissingParameterException
     * @throws NotFoundError
     */
    protected function addComputeCluster(): ComputeCluster
    {
        $computeCluster = ComputeCluster::loadWithUuid($this->params->getRequired('uuid'), $this->db());
        $this->getRestrictionHelper()->assertAccessToVCenterUuidIsGranted($computeCluster->get('vcenter_uuid'));
        $this->controls()->add(new ComputeClusterHeader($computeCluster));
        $this->setTitle($computeCluster->get('object_name'));
        $this->handleTabs($computeCluster);

        return $computeCluster;
    }

    /**
     * @param ComputeCluster $computeCluster
     *
     * @return void
     *
     * @throws MissingParameterException
     */
    protected function handleTabs(ComputeCluster $computeCluster): void
    {
        $hexId = $this->params->getRequired('uuid');
        $this->tabs()->add('index', [
            'label' => $this->translate('Compute Cluster'),
            'url' => 'vspheredb/compute-cluster',
            'urlParams' => ['uuid' => $hexId]
        ])/*->add('hosts', [
            'label' => sprintf(
                $this->translate('Host Systems (%d)'),
                $computeCluster->countHosts()
            ),
            'url' => 'vspheredb/compute-cluster/hosts',
            'urlParams' => ['uuid' => $hexId]
        ])*/->activate($this->getRequest()->getActionName());
    }
}
