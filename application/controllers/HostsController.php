<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Link;
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
            $this->content()->add(new OverviewTree($this->db(), 'host'));
            return;
        }
        $table = new HostsTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $this->showTable($table, 'vspheredb/hosts', $this->translate('Hosts'));
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }

    protected function addTreeViewToggle()
    {
        if ($this->params->get('render') === 'tree') {
            $this->actions()->add(
                Link::create(
                    $this->translate('Table'),
                    $this->url()->without('render'),
                    null,
                    ['class' => 'icon-sitemap']
                )
            );
        } else {
            $this->actions()->add(
                Link::create(
                    $this->translate('Tree'),
                    $this->url()->with('render', 'tree'),
                    null,
                    ['class' => 'icon-sitemap']
                )
            );
        }
    }
}
