<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Table\Objects\DatastoreTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class DatastoresController extends ObjectsController
{
    public function indexAction()
    {
        $this->handleTabs();
        $this->addTreeViewToggle();
        if ($this->params->get('render') === 'tree') {
            $this->addTitle($this->translate('Datastores'));
            $this->content()->add(new OverviewTree($this->db(), 'datastore'));

            return;
        }

        $this->setAutorefreshInterval(15);
        $table = new DatastoreTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/datastores', $this->translate('Datastores'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
