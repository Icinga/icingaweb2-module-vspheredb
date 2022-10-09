<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualPortgroup;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\NetworkAdaptersTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class PortgroupController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $this->setAutorefreshInterval(15);
        $table = new NetworkAdaptersTable($this->db(), $this->url());
        $portGroup = DistributedVirtualPortgroup::loadWithUuid($this->params->getRequired('uuid'), $this->db());
            $table->filterPortGroup($portGroup);
        $this->addSingleTab($this->translate('Port Group'));
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, $this->url(), $this->translate('Virtual Machine Network Adapters'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
