<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterSummaryTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Config\ProposeMigrations;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;
use Icinga\Module\Vspheredb\Web\Widget\ResourceUsageLoader;
use Icinga\Module\Vspheredb\Web\Widget\UsageSummary;
use Icinga\Module\Vspheredb\WebUtil;
use ipl\Html\Html;

class VcentersController extends ObjectsController
{
    use AsyncControllerHelper;

    protected function getConnectionsByVCenter()
    {
        try {
            $connections = $this->syncRpcCall('vsphere.getApiConnections');
        } catch (\Exception $e) {
            return null;
        }
        $connectionsByVCenter = $this->getConfiguredServersByVCenter();
        foreach ((array) $connections as $id => $connection) {
            if (isset($connectionsByVCenter[$connection->vcenter_id])) {
                $connectionsByVCenter[$connection->vcenter_id][$connection->server_id]->state = $connection->state;
            } else {
                $connectionsByVCenter[$connection->vcenter_id] = [$connection->server_id => (object) [
                    'state'  => $connection->state,
                    'server' => $connection->server,
                ]];
            }
        }

        return $connectionsByVCenter;
    }

    protected function getConfiguredServersByVCenter()
    {
        $db = $this->db()->getDbAdapter();
        $result = [];
        foreach ($db->fetchAll(
            $db->select()->from('vcenter_server', [
                'id',
                'vcenter_id',
                'host',
                'enabled'
            ])
        ) as $server) {
            if (! isset($result[$server->vcenter_id])) {
                $result[$server->vcenter_id] = [];
            }
            $result[$server->vcenter_id][$server->id] = (object) [
                'server' => $server->host,
                'enabled' => $server->enabled === 'y',
                'state'   => $server->enabled === 'n' ? 'disabled' : 'unknown',
            ];
        }

        return $result;
    }

    /**
     * @throws \Zend_Db_Select_Exception
     */
    public function indexAction()
    {
        $this->setAutorefreshInterval(15);
        $this->addSingleTab($this->translate('VCenters'));
        $this->handleTabs();
        $this->checkDaemonStatus();
        $this->checkForMigrations();
        $table = new VCenterSummaryTable($this->db(), $this->url());
        if (null !== ($connections = $this->getConnectionsByVCenter())) {
            $table->setConnections($connections);
        }
        $this->actions()->add(Link::create(
            $this->translate('Add Connection'),
            'vspheredb/vcenter/server',
            null,
            [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ]
        ));
        /*
        $this->actions()->add(Link::create(
            $this->translate('Chart'),
            '#',
            null,
            ['class' => 'icon-chart-pie']
        ));
        */
        $count = count($table);
        $this->addTitle($this->translate('Monitored vCenters') . ' (%d)', $count);
        if ($count === 0) {
            $this->addNoVCenterHint();
            return;
        }
        $this->content()->add(new UsageSummary(
            (new ResourceUsageLoader($this->db()->getDbAdapter()))->fetch()
        ));

        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/groupedvms');
        // $this->controls()->prepend($this->cpuSummary($table));
    }

    protected function addNoVCenterHint()
    {
        $this->content()->add(Hint::warning(
            $this->translate('No vCenter available. You might want to check your %s or your %s'),
            Link::create(
                $this->translate('Server Connections'),
                'vspheredb/configuration/servers'
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

    protected function checkDaemonStatus()
    {
        $db = $this->db()->getDbAdapter();
        $daemon = $db->fetchRow(
            $db->select()
                ->from('vspheredb_daemon')
                ->order('ts_last_refresh DESC')
                ->limit(1)
        );

        if ($daemon) {
            if ($daemon->ts_last_refresh / 1000 < time() - 10) {
                $info = Hint::error(Html::sprintf(
                    "Daemon keep-alive is outdated, last refresh was %s",
                    WebUtil::timeAgo($daemon->ts_last_refresh / 1000)
                ));
                $this->content()->add($info);
            }
        } else {
            $info = Hint::error($this->translate('Daemon is not running'));
            $this->content()->add($info);
        }
    }

    protected function checkForMigrations()
    {
        if (Db::migrationsForDb($this->db())->hasPendingMigrations()) {
            $this->redirectNow('configuration/database');
        };

        return;

        // Obsolete:
        /*
        $migrations = new ProposeMigrations($this->db(), $this->Auth(), $this->getServerRequest());
        if ($migrations->hasAppliedMigrations()) {
            $this->redirectNow($this->url());
        }
        $this->content()->add($migrations);
        */
    }
}
