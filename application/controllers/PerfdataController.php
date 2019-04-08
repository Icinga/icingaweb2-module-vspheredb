<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Form\FilterVCenterForm;
use Icinga\Module\Vspheredb\Web\Table\PerformanceCounterTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;

class PerfdataController extends Controller
{
    public function init()
    {
        $this->assertPermission('vspheredb/admin');
    }

    public function indexAction()
    {
        $this->addTitle($this->translate('Performance Data'));
        $this->handleTabs();
        $this->content()->add(Html::tag('p', $this->translate(
            'This module can collect Performance Data from your vCenters or ESXi Hosts.'
            . ' Different on '
        )));
    }

    public function countersAction()
    {
        $this->handleTabs();
        $this->addTitle($this->translate('Available Performance Counters'));
        $form = new FilterVCenterForm($this->db());
        $form->handleRequest($this->getRequest());
        $this->content()->add(Html::tag('div', ['class' => 'icinga-module module-director'], $form));
        $table = (new PerformanceCounterTable($this->db()))
            ->filterVCenterUuid($form->getHexUuid());
        (new AdditionalTableActions($table, $this->Auth(), $this->url()))
            ->appendTo($this->actions());
        $table->handleSortUrl($this->url());
        $table->renderTo($this);
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getActionName();
        $this->tabs()->add('index', [
            'label' => $this->translate('Performance Data'),
            'url'   => 'vspheredb/perfdata',
        ])->add('counters', [
            'label' => $this->translate('Counters'),
            'url'   => 'vspheredb/perfdata/counters',
        ])->activate($action);
    }
}
