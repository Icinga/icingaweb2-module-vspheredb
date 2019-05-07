<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Format;
use ipl\Html\Html;

abstract class HostSummaryTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/computeresource';

    protected $searchColumns = [
        'name',
    ];

    protected $groupByAlias = 'name';

    protected $nameColumn;

    protected $groupBy;

    abstract protected function getFilterParams($row);

    abstract protected function getGroupingTitle();

    protected function prepareUnGroupedQuery()
    {
        return $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['ho' => 'object'],
            'ho.parent_uuid = o.uuid',
            []
        )->join(
            ['h' => 'host_system'],
            'ho.uuid = h.uuid',
            []
        )->join(
            ['hqs' => 'host_quick_stats'],
            'hqs.uuid = h.uuid',
            []
        )->where('h.runtime_power_state = ?', 'poweredOn');
    }

    public function prepareQuery()
    {
        $query = $this->prepareUnGroupedQuery();
        if ($this->parentUuids) {
            $query->where('o.uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('o.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        if ($this->groupBy !== null) {
            $query->group($this->groupBy);
        }

        return $query;
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
                $link,
                Html::tag('br'),
                Html::tag('small', $this->renderHostSummaries($row)),
            ];
        });
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createGroupingColumn(),

            $this->createColumn('hardware_cpu_cores', 'Cores', 'SUM(h.hardware_cpu_cores)')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('cpu', 'CPU', [
                'used_mhz'  => 'SUM(hqs.overall_cpu_usage)',
                'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
            ])->setRenderer(function ($row) {
                return new CpuUsage($row->used_mhz, $row->total_mhz);
            })->setSortExpression(
                'SUM(hqs.overall_cpu_usage) / SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)'
            )->setDefaultSortDirection('DESC'),

            $this->createColumn('memory', 'Memory', [
                'used_mb'  => 'SUM(hqs.overall_memory_usage_mb)',
                'total_mb' => 'SUM(h.hardware_memory_size_mb)',
            ])->setRenderer(function ($row) {
                return new MemoryUsage($row->used_mb, $row->total_mb);
            })->setSortExpression(
                'AVG(hqs.overall_memory_usage_mb / h.hardware_memory_size_mb)'
            )->setDefaultSortDirection('DESC'),

            $this->createColumn('cnt_hosts', 'Hosts', 'COUNT(*)')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn(
                'hosts_status',
                $this->translate('Hosts Status'),
                $this->getHostCountColumns()
            )->setRenderer(function ($row) {
                $result = [];
                foreach (['red', 'yellow', 'gray', 'green'] as $state) {
                    $column = "hosts_cnt_overall_$state";
                    if ($row->$column > 0) {
                        $result[] = Link::create($row->$column, 'vspheredb/hosts', [
                            'uuid'           => bin2hex($row->uuid),
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
                'hosts_cnt_overall_red',
                'hosts_cnt_overall_yellow',
                'hosts_cnt_overall_gray',
                'hosts_cnt_overall_green',
            ])->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_cpu_mhz', 'CPU Capacity', 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->hardware_cpu_mhz);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('overall_cpu_usage', 'CPU Usage', 'SUM(hqs.overall_cpu_usage)')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->overall_cpu_usage);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_memorymb', 'Memory Capacity', 'SUM(h.hardware_memory_size_mb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('overall_memory_usage', 'Memory Usage', 'SUM(hqs.overall_memory_usage_mb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->overall_memory_usage);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('projects', 'Projects', '(null)')
                ->setRenderer(function ($row) {
                    return Link::create(
                        Icon::create('right-big'),
                        'vspheredb/resources/projects',
                        $this->getFilterParams($row),
                        ['data-base-target' => '_next']
                    );
                }),
        ]);
    }

    protected function getHostCountColumns()
    {
        return [
            'hosts_cnt' => 'COUNT(*)',
            'hosts_cnt_overall_gray'   => "SUM(CASE WHEN ho.overall_status = 'gray' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_green'  => "SUM(CASE WHEN ho.overall_status = 'green' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_yellow' => "SUM(CASE WHEN ho.overall_status = 'yellow' THEN 1 ELSE 0 END)",
            'hosts_cnt_overall_red'    => "SUM(CASE WHEN ho.overall_status = 'red' THEN 1 ELSE 0 END)",
        ];
    }

    protected function renderHostSummaries($row)
    {
        $params = $this->getFilterParams($row);
        $hosts = [
            Link::create(
                (int) $row->hosts_cnt === 1
                    ? $this->translate('1 Host')
                    : sprintf($this->translate('%d Hosts'), $row->hosts_cnt),
                'vspheredb/hosts',
                $params
            ),
            ': '
        ];
        foreach (['red', 'yellow', 'gray', 'green'] as $state) {
            $column = "hosts_cnt_overall_$state";
            if ($row->$column > 0) {
                $hosts[] = Link::create($row->$column, 'vspheredb/hosts', $params + [
                        'overall_status' => $state
                    ], ['class' => ['state', $state]]);
            }
        }

        return $hosts;
    }

    public function getDefaultColumnNames()
    {
        return [
            $this->groupByAlias,
            'cpu',
            'memory'
        ];
    }
}
