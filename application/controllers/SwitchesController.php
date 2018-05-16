<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\SwitchesTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class SwitchesController extends ObjectsController
{
    /**
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function indexAction()
    {
        $this->handleTabs();
        $this->setAutorefreshInterval(15);
        $table = new SwitchesTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/switches', $this->translate('(Distributed) Virtual Switches'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
