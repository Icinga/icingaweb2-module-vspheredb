<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Format;

class GroupedvmsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    protected $groupByAlias = 'project';

    protected $groupBy = '(SUBSTR(o.object_name, 1, POSITION(\'-\' IN o.object_name) - 1))';

    public function filter($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        )->group($this->groupByAlias);

        $query->join(
            ['h' => 'host_system'],
            'vc.runtime_host_uuid = h.uuid',
            []
        )->join(
            ['ho' => 'object'],
            'ho.uuid = h.uuid',
            []
        );
        $query->join(
            ['vqs' => 'vm_quick_stats'],
            'vqs.uuid = vc.uuid',
            []
        );

        if ($this->parentUuids) {
            $query->where('ho.parent_uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('ho.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        return $query;
    }

    protected function createGroupingColumn()
    {
        return $this->createColumn($this->groupByAlias, 'Project', $this->groupBy)
            ->setRenderer(function ($row) {
                $groupName = $row->{$this->groupByAlias};

                return Link::create(
                    $groupName,
                    'vspheredb/vms',
                    [
                        'computeCluster' => bin2hex($this->parentUuids[0]),
                        'q' => $groupName . '-*', // TODO: allow wildcard for object_name?
                    ],
                    ['data-base-target' => '_next']
                );
            });
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createGroupingColumn(),
            /*
            $this->createColumn('cpu', 'CPU', [
                'used_mhz'  => 'SUM(vqs.overall_cpu_usage)',
                'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
            ])->setRenderer(function ($row) {
                return new CpuUsage($row->used_mhz, $row->total_mhz);
            })->setSortExpression(
                'SUM(hqs.overall_cpu_usage) / SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)'
            )->setDefaultSortDirection('DESC'),
            */
            $this->createColumn('hardware_numcpu', $this->translate('vCPU Count'), 'SUM(vc.hardware_numcpu)')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('cpu_usage', 'CPU', 'SUM(vqs.overall_cpu_usage)')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->cpu_usage);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('memory', $this->translate('Memory'), [
                'used_mb'      => 'SUM(vqs.guest_memory_usage_mb)',
                'total_mb'     => 'SUM(vc.hardware_memorymb)',
                'host_used_mb' => 'SUM(vqs.host_memory_usage_mb)',
            ])->setRenderer(function ($row) {
                return new MemoryUsage($row->used_mb, $row->total_mb, $row->host_used_mb);
            })->setSortExpression(
                'SUM(vqs.guest_memory_usage_mb) / SUM(vc.hardware_memorymb)'
            )->setDefaultSortDirection('DESC'),

            $this->createColumn('host_memory', $this->translate('Host Memory'), [
                'host_used_mb'  => 'SUM(vqs.host_memory_usage_mb)',
                'total_mb' => 'SUM(vc.hardware_memorymb)',
            ])->setRenderer(function ($row) {
                return new MemoryUsage($row->host_used_mb, $row->total_mb);
            })->setSortExpression(
                'AVG(vqs.host_memory_usage_mb / vc.hardware_memorymb)'
            )->setDefaultSortDirection('DESC'),

            /*
            $this->createColumn('memory', 'Host Memory', [
                'used_mb'  => 'SUM(vqs.host_memory_usage_mb)',
                'total_mb' => 'SUM(vc.hardware_memorymb)',
            ])->setRenderer(function ($row) {
                $used = $row->used_mb * 1024 * 1024;
                $total = $row->total_mb * 1024 * 1024;
                $title = sprintf(
                    '%s / %s',
                    Format::bytes($used),
                    Format::bytes($total)
                );

                return [
                    new SimpleUsageBar($used, $total, $title),
                    Html::tag('small', ['style' => 'float: left'], 'Used: ' . Format::bytes($used)),
                    Html::tag('small', ['style' => 'float: right'], 'Capacity: ' . Format::bytes($total)),
                ];
            })->setSortExpression(
                'AVG(hqs.overall_memory_usage_mb / h.hardware_memory_size_mb)'
            )->setDefaultSortDirection('DESC'),
*/

            $this->createColumn('vms', 'VMs', 'COUNT(*)')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memorymb', 'Memory Capacity', 'SUM(vc.hardware_memorymb)')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),

        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'project',
            'memory',
            'cpu_usage',
            'memory_usage'
        ];
    }
}
