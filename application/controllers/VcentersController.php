<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterSummaryTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Config\ProposeMigrations;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;
use ipl\Html\Html;

class VcentersController extends ObjectsController
{
    /**
     * @throws \Zend_Db_Select_Exception
     */
    public function indexAction()
    {
        $migrations = new ProposeMigrations($this->db(), $this->Auth(), $this->getServerRequest());
        if ($migrations->hasAppliedMigrations()) {
            $this->redirectNow($this->url());
        }
        $this->content()->add($migrations);
        $this->setAutorefreshInterval(15);
        $this->addSingleTab($this->translate('VCenters'));
        $this->handleTabs();

        $this->setAutorefreshInterval(15);
        $table = new VCenterSummaryTable($this->db(), $this->url());
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
        $count = count($table);
        $this->addTitle($this->translate('VCenters') . ' (%d)', $count);
        if ($count === 0) {
            $this->addNoVCenterHint();
        }
        $this->showTable($table, 'vspheredb/groupedvms');
        $this->controls()->prepend($this->cpuSummary($table));
    }

    protected function addNoVCenterHint()
    {
        $this->content()->add(Hint::warning(
            $this->translate('No vCenter available. You might want to check your %s or your %s'),
            Link::create(
                $this->translate('Server Connections'),
                'vspheredb/vcenter/servers'
            ),
            Link::create(
                $this->translate('Daemon Status'),
                'vspheredb/daemon'
            )
        ));
    }

    /**
     * @param VCenterSummaryTable $table
     * @return CpuAbsoluteUsage
     * @throws \Zend_Db_Select_Exception
     */
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
