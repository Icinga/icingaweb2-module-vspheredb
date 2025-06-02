<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Monitoring\Health\ServerConnectionInfo;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\VCenterConnectionStatusIcon;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class VCenterSummaryTable extends ObjectsTable
{
    protected $searchColumns = [
        'name',
    ];
    protected $baseUrl = 'vspheredb/vcenter';

    protected $baseUrlHosts = 'vspheredb/hosts';

    protected $groupBy = 'o.vcenter_uuid';

    protected $groupByAlias = 'name';

    protected $nameColumn = 'vc.name';

    /** @var array<int, array<int, ServerConnectionInfo>> */
    protected $connections;

    /**
     * @param array<int, array<int, ServerConnectionInfo>> $connections
     * @return VCenterSummaryTable
     */
    public function setConnections(array $connections): self
    {
        $this->connections = $connections;
        return $this;
    }

    protected function getExtraIcons($row)
    {
        if ($this->connections === null) {
            return null;
        }

        $icons = Html::tag('span', ['class' => 'vcenter-summary-icon']);
        $vcenterId = $row->vcenter_id;
        if (isset($this->connections[$vcenterId])) {
            foreach ($this->connections[$vcenterId] as $connection) {
                $icons->add(VCenterConnectionStatusIcon::create($connection));
            }
        } else {
            $icons->add(VCenterConnectionStatusIcon::noServer());
        }

        return $icons;
    }

    public function getDefaultColumnNames()
    {
        return [
            $this->groupByAlias,
            'cpu',
            'memory',
            'datastore_usage',
        ];
    }

    protected function initialize()
    {
        $this->setAttribute('data-base-target', '_self');
        $this->addAvailableColumns([
            $this->createGroupingColumn(),
        ]);
        $this->addHostColumns();
        $this->addDatastoreColumns();
        $this->addVCenterColumns();
    }

    protected function createGroupingColumn()
    {
        return $this->createColumn($this->groupByAlias, $this->getGroupingTitle(), [
                'name'         => $this->nameColumn,
                'uuid'         => $this->groupBy,
            ] + $this->getHostCountColumns())->setRenderer(function ($row) {
                $link = Link::create(
                    $row->{$this->groupByAlias},
                    $this->baseUrl,
                    $this->getFilterParams($row),
                    ['data-base-target' => '_next']
                );

                return [
                    $this->getExtraIcons($row),
                    $link,
                    $this->renderHostSummaries($row),
                ];
            });
    }

    protected function hasChosenColumn($name)
    {
        return in_array($name, $this->getChosenColumnNames());
    }

    protected function addHostColumns()
    {
        $this->addAvailableColumns([
            $this->createColumn('hosts_cnt', $this->translate('Hosts'), 'SUM(hosts_cnt)')
                ->setRenderer(function ($row) {
                    return Html::tag('td', ['class' => 'text-right'], $row->hosts_cnt);
                })
                ->setDefaultSortDirection('DESC'),
            $this->createColumn(
                'hosts_status',
                $this->translate('Hosts Status'),
                $this->getHostCountColumns()
            )->setRenderer(function ($row) {
                $result = [];
                $uuid = Uuid::fromBytes($row->uuid)->toString();
                foreach (['green', 'gray', 'yellow', 'red'] as $state) {
                    $column = "hosts_cnt_overall_$state";
                    if ($row->$column > 0) {
                        $result[] = Link::create($row->$column, $this->baseUrlHosts, [
                            'vcenter'        => $uuid,
                            'overall_status' => $state
                        ], ['class' => ['state', $state]]);
                    }
                }

                if (empty($result)) {
                    return '-';
                } else {
                    return $result;
                }
            })->setSortExpression([
                "SUM(CASE WHEN ho.overall_status = 'red' THEN 1 ELSE 0 END)",
                "SUM(CASE WHEN ho.overall_status = 'yellow' THEN 1 ELSE 0 END)",
                "SUM(CASE WHEN ho.overall_status = 'gray' THEN 1 ELSE 0 END)",
                "SUM(CASE WHEN ho.overall_status = 'green' THEN 1 ELSE 0 END)",
            ])->setDefaultSortDirection('DESC'),
            $this->createColumn('cpu', $this->translate('CPU'), [
                'used_mhz'  => 'SUM(hqs.overall_cpu_usage)',
                'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
            ])->setRenderer(function ($row) {
                $bar = new CpuUsage($row->used_mhz, $row->total_mhz);
                if ($this->hasChosenColumn('overall_cpu_usage') || $this->hasChosenColumn('hardware_cpu_mhz')) {
                    $bar->showLabels(false);
                }
                return $bar;
            })->setSortExpression(
                '(overall_cpu_usage / total_mhz)'
            )->setDefaultSortDirection('DESC'),
            $this->createColumn('overall_cpu_usage', $this->translate('Used'), 'SUM(hqs.overall_cpu_usage)')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->overall_cpu_usage);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn(
                'hardware_cpu_mhz',
                $this->translate('Capacity'),
                'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)'
            )->setRenderer(function ($row) {
                return Format::mhz($row->hardware_cpu_mhz);
            })->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_cpu_cores', $this->translate('Cores'), 'SUM(h.hardware_cpu_cores)')
                ->setRenderer(function ($row) {
                    return Html::tag('td', ['class' => 'text-right'], $row->hardware_cpu_cores);
                })
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('memory', $this->translate('Memory'), [
                'memory_used_mb'  => 'SUM(hqs.overall_memory_usage_mb)',
                'memory_total_mb' => 'SUM(h.hardware_memory_size_mb)',
            ])->setRenderer(function ($row) {
                $bar = new MemoryUsage($row->memory_used_mb, $row->memory_total_mb);
                if ($this->hasChosenColumn('overall_memory_usage') || $this->hasChosenColumn('hardware_memorymb')) {
                    $bar->showLabels(false);
                }
                return $bar;
            })->setSortExpression(
                '(memory_used_mb / memory_total_mb)'
            )->setDefaultSortDirection('DESC'),
            $this->createColumn('overall_memory_usage', $this->translate('Used'), 'SUM(hqs.overall_memory_usage_mb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->overall_memory_usage);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memory_mb', $this->translate('Capacity'))
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memory_mb);
                })->setDefaultSortDirection('DESC'),
        ]);
    }

    protected function addDatastoreColumns()
    {
        $this->addAvailableColumns([
            $this->createColumn('datastore_usage', $this->translate('Storage'), [
                'ds_capacity'   => 'ds.ds_capacity',
                'ds_free_space' => 'ds.ds_free_space',
            ])->setRenderer(function ($row) {
                return new MemoryUsage(
                    ($row->ds_capacity - $row->ds_free_space) / 1000000,
                    $row->ds_capacity / 1000000
                );
            })->setSortExpression('(ds.ds_capacity - ds.ds_free_space) / ds.ds_capacity'),
        ]);
    }

    protected function addVCenterColumns()
    {
        $this->addAvailableColumns([
            $this->createColumn('vcenter_software', $this->translate('Software'), [
                'software_name' => 'vc.api_name',
                'software_version' => 'vc.version',
            ])->setRenderer(function ($row) {
                // VMware ESXi -> ESXi
                return \sprintf(
                    '%s (%s)',
                    \preg_replace('/^VMware /', '', $row->software_name),
                    $row->software_version
                );
            }),
        ]);
    }

    protected function renderHostSummaries($row)
    {
        $params = $this->getFilterParams($row);

        $result = [];
        if (! $this->hasChosenColumn('cnt_hosts')) {
            $result[] = Link::create(
                (int) $row->hosts_cnt === 1
                    ? $this->translate('1 Host')
                    : sprintf($this->translate('%d Hosts'), $row->hosts_cnt),
                'vspheredb/hosts',
                $params
            );
        }

        if (! $this->hasChosenColumn('hosts_status')) {
            if (! empty($result)) {
                $result[] = ': ';
            }
            foreach (['red', 'yellow', 'gray', 'green'] as $state) {
                $column = "hosts_cnt_overall_$state";
                if ($row->$column > 0) {
                    $result[] = Link::create($row->$column, 'vspheredb/hosts', $params + [
                            'overall_status' => $state
                        ], ['class' => ['state', $state]]);
                }
            }
        }

        if (empty($result)) {
            return null;
        }
        return [
            Html::tag('br'),
            Html::tag('small', $result),
        ];
    }

    protected function getHostCountColumns()
    {
        return [
            'hosts_cnt' => 'COUNT(DISTINCT ho.uuid)',
            'hosts_cnt_overall_gray'   => "SUM(CASE WHEN ho.overall_status = 'gray' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_green'  => "SUM(CASE WHEN ho.overall_status = 'green' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_yellow' => "SUM(CASE WHEN ho.overall_status = 'yellow' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_red'    => "SUM(CASE WHEN ho.overall_status = 'red' THEN 1 ELSE 0 END)",
        ];
    }

    protected function prepareHostsQuery()
    {
        return $this->db()->select()->from(
            ['h' => 'host_system'],
            [
                'vcenter_uuid'             => 'h.vcenter_uuid',
                'hosts_cnt'                => 'COUNT(DISTINCT h.uuid)',
                'hosts_cnt_overall_gray'   => "SUM(CASE WHEN ho.overall_status = 'gray' THEN 1 ELSE 0 END)",
                'hosts_cnt_overall_green'  => "SUM(CASE WHEN ho.overall_status = 'green' THEN 1 ELSE 0 END)",
                'hosts_cnt_overall_yellow' => "SUM(CASE WHEN ho.overall_status = 'yellow' THEN 1 ELSE 0 END)",
                'hosts_cnt_overall_red'    => "SUM(CASE WHEN ho.overall_status = 'red' THEN 1 ELSE 0 END)",

                'used_mhz'                 => 'COALESCE(SUM(hqs.overall_cpu_usage), 0)',
                'total_mhz'                => 'COALESCE(SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz), 0)',
                'overall_cpu_usage'        => 'SUM(hqs.overall_cpu_usage)',
                'hardware_cpu_mhz'         => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
                'hardware_cpu_cores'       => 'SUM(h.hardware_cpu_cores)',

                'memory_used_mb'           => 'SUM(hqs.overall_memory_usage_mb)',
                'memory_total_mb'          => 'SUM(h.hardware_memory_size_mb)',
                'overall_memory_usage'     => 'SUM(hqs.overall_memory_usage_mb)',
                'hardware_memory_mb'       => 'SUM(h.hardware_memory_size_mb)',
            ]
        )->join(
            ['ho' => 'object'],
            'ho.uuid = h.uuid',
            []
        )->join(
            ['hqs' => 'host_quick_stats'],
            'hqs.uuid = h.uuid',
            []
        )
            ->group('h.vcenter_uuid');
    }

    protected function prepareDatastoreQuery()
    {
        return $this->db()->select()->from(
            // TODO: Join object?
            ['ds' => 'datastore'],
            [
                'vcenter_uuid'   => 'ds.vcenter_uuid',
                'ds_capacity'    => 'SUM(ds.capacity)',
                'ds_free_space'  => 'SUM(ds.free_space)',
                'ds_uncommitted' => 'SUM(ds.uncommitted)',
            ]
        )->group('ds.vcenter_uuid');
    }

    protected function prepareQuery()
    {
        $vCenterColumns = [
            'uuid' => 'vc.instance_uuid',
            'vcenter_id' => 'vc.id',
            'name' => 'vc.name',
            'software_name' => 'vc.api_name',
            'software_version' => 'vc.version',
        ];

        return $this->db()->select()->from(
            ['vc' => 'vcenter'],
            $vCenterColumns + ['h.*', 'ds.*']
        )->joinLeft(
            ['ds' => $this->prepareDatastoreQuery()],
            'vc.instance_uuid = ds.vcenter_uuid',
            []
        )->joinLeft(
            ['h' => $this->prepareHostsQuery()],
            'vc.instance_uuid = h.vcenter_uuid',
            []
        );
    }

    protected function getGroupingTitle()
    {
        return $this->translate('VCenter');
    }

    protected function getFilterParams($row)
    {
        return ['vcenter' => Util::niceUuid($row->uuid)];
    }
}
