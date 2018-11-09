<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Link;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterSummaryTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;

class VcentersController extends ObjectsController
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(15);
        $this->addSingleTab($this->translate('VCenters'));
        $this->handleTabs();

        $this->setAutorefreshInterval(15);
        $table = new VCenterSummaryTable($this->db());
        /*
        $this->actions()->add(Link::create(
            $this->translate('Chart'),
            '#',
            null,
            ['class' => 'icon-chart-pie']
        ));
        */
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->addTitle($this->translate('VCenters') . ' (%d)', count($table));
        $this->showTable($table, 'vspheredb/groupedvms');
        $table->handleSortUrl($this->url());
        $this->controls()->prepend($this->cpuSummary($table));
    }

    protected function cpuSummary(VCenterSummaryTable $table)
    {
        $query = clone($table->getQuery());
        $query->reset('columns')->reset('limitcount')->reset('limitoffset')->reset('group');
        $query->columns([
            'used_mhz'  => 'SUM(hqs.overall_cpu_usage)',
            'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
            'used_mb'   => 'SUM(hqs.overall_memory_usage_mb)',
            'total_mb'  => 'SUM(h.hardware_memory_size_mb)',
        ]);

        $total = $this->db()->getDbAdapter()->fetchRow($query);

        return new CpuAbsoluteUsage(
            $total->used_mhz
        );
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getControllerName();
        $tabs = $this->tabs(new MainTabs($this->Auth(), $this->db()));
        if ($tabs->has($action)) {
            $tabs->activate($action);
        } else {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
