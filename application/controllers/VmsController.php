<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Table;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class VmsController extends ObjectsController
{
    public function indexAction()
    {
        $this->handleTabs();
        $this->linkBackToOverview('vm');

        $table = new VmsTable($this->db());
        $table->handleSortUrl($this->url());

        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());

        $this->showTable(
            $table,
            'vspheredb/vms',
            $this->translate('Virtual Machines')
        );
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries->addPowerState());
    }
}
