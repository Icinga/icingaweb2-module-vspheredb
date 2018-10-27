<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualSwitch;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\PortGroupsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class SwitchController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $this->setAutorefreshInterval(15);
        $table = new PortGroupsTable($this->db());
        $switch = DistributedVirtualSwitch::load(hex2bin(
            $this->params->getRequired('uuid')
        ), $this->db());
            $table->filterSwitch($switch);
        $this->addSingleTab($this->translate('Switch'));
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, $this->url(), $this->translate('Virtual Port Groups'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
