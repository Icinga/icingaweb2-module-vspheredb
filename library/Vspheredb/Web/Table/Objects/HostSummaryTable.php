<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Format;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

abstract class HostSummaryTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/computeresource';

    protected $baseUrlHosts = 'vspheredb/hosts';

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
                $this->getExtraIcons($row),
                $link,
                $this->renderHostSummaries($row),
            ];
        });
    }

    protected function getExtraIcons($row)
    {
    }

    protected function hasChosenColumn($name)
    {
        return in_array($name, $this->getChosenColumnNames());
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createGroupingColumn(),
            $this->createColumn('cnt_hosts', $this->translate('Hosts'), 'COUNT(*)')
                ->setRenderer(function ($row) {
                    return Html::tag('td', ['style' => 'text-align: right'], $row->cnt_hosts);
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
                'SUM(hqs.overall_cpu_usage) / SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)'
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
                    return Html::tag('td', ['style' => 'text-align: right'], $row->hardware_cpu_cores);
                })
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('memory', $this->translate('Memory'), [
                'used_mb'  => 'SUM(hqs.overall_memory_usage_mb)',
                'total_mb' => 'SUM(h.hardware_memory_size_mb)',
            ])->setRenderer(function ($row) {
                $bar = new MemoryUsage($row->used_mb, $row->total_mb);
                if ($this->hasChosenColumn('overall_memory_usage') || $this->hasChosenColumn('hardware_memorymb')) {
                    $bar->showLabels(false);
                }
                return $bar;
            })->setSortExpression(
                'AVG(hqs.overall_memory_usage_mb / h.hardware_memory_size_mb)'
            )->setDefaultSortDirection('DESC'),
            $this->createColumn('overall_memory_usage', $this->translate('Used'), 'SUM(hqs.overall_memory_usage_mb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->overall_memory_usage);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memorymb', $this->translate('Capacity'), 'SUM(h.hardware_memory_size_mb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),

            /*
             // Not yet, this was an early prototype based no monitoring vars
            $this->createColumn('projects', 'Projects', '(null)')
                ->setRenderer(function ($row) {
                    return Link::create(
                        Icon::create('right-big'),
                        'vspheredb/resources/projects',
                        $this->getFilterParams($row),
                        ['data-base-target' => '_next']
                    );
                }),
             */
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

    public function getDefaultColumnNames()
    {
        return [
            $this->groupByAlias,
            'cpu',
            'memory'
        ];
    }
}
