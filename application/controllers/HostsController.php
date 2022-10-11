<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class HostsController extends ObjectsController
{
    public function indexAction()
    {
        $this->handleTabs();
        $this->addTreeViewToggle();
        if ($this->params->get('render') === 'tree') {
            $this->addTitle($this->translate('Hosts'));
            $this->content()->add(new OverviewTree($this->db(), $this->getRestrictionHelper(), 'host'));

            return;
        }

        $this->setAutorefreshInterval(15);
        $table = new HostsTable($this->db(), $this->url());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/hosts', $this->translate('Hosts'));
        // Hint: handleSortUrl MUST be done AFTER showTable, otherwise
        //       eventuallyFilter and similar will not be applied
        // TODO: This is error-prone and should be solved differently. And right now
        //       (with the url in the constructor) it will no longer be possible.
        //       CHECK THIS!
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
