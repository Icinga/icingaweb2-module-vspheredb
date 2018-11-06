<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Link;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterSummaryTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;

class VcentersController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('VCenters'));
        $this->handleTabs();

        $this->setAutorefreshInterval(15);
        $table = new VCenterSummaryTable($this->db());
        $this->actions()->add(Link::create(
            $this->translate('Chart'),
            '#',
            null,
            ['class' => 'icon-chart-pie']
        ));
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->addTitle($this->translate('VCenters') . ' (%d)', count($table));
        $this->showTable($table, 'vspheredb/groupedvms');
        $table->handleSortUrl($this->url());
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getControllerName();
        $tabs = $this->tabs(new MainTabs($this->db()));
        if ($tabs->has($action)) {
            $tabs->activate($action);
        } else {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
