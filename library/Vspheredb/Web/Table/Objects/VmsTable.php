<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Widget\DelayedPerfdataRenderer;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Format;

class VmsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    protected $searchColumns = [
        'object_name',
        'guest_host_name',
        'guest_ip_address'
    ];

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $wantsHosts = false;
        $wantsStats = false;
        foreach ($columns as $column) {
            if (substr($column, 0, 2) === 'h.') {
                $wantsHosts = true;
            }
            if (substr($column, 0, 4) === 'vqs.') {
                $wantsStats = true;
            }
        }

        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        );

        if ($wantsStats) {
            $query->join(
                ['vqs' => 'vm_quick_stats'],
                'vqs.uuid = vc.uuid',
                []
            );
        }

        if ($wantsHosts) {
            $query->joinLeft(
                ['h' => 'host_system'],
                'vc.runtime_host_uuid = h.uuid',
                []
            );
        }
        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('o.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        return $query;
    }

    protected function initialize()
    {
        $powerStateRenderer = new PowerStateRenderer();
        $memoryRenderer = function ($row) {
            return new MemoryUsage(
                $row->guest_memory_usage_mb,
                $row->hardware_memorymb,
                $row->host_memory_usage_mb
            );
        };
        $memoryColumns = [
            'guest_memory_usage_mb' => 'vqs.guest_memory_usage_mb',
            'host_memory_usage_mb'  => 'vqs.host_memory_usage_mb',
            'hardware_memorymb'     => 'vc.hardware_memorymb',
        ];
        $this->addAvailableColumns([
            $this->createColumn('runtime_power_state', $this->translate('Power'), 'vc.runtime_power_state')
                ->setRenderer($powerStateRenderer),
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createColumn('host_name', $this->translate('Host'), 'h.host_name'),
            $this->createColumn('guest_ip_address', $this->translate('Guest IP'), 'vc.guest_ip_address'),
            $this->createColumn('hardware_numcpu', 'vCPUs', 'vc.hardware_numcpu')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('cpu_usage', $this->translate('CPU Usage'), 'vqs.overall_cpu_usage')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->cpu_usage);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_memorymb', $this->translate('Memory'), 'vc.hardware_memorymb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('guest_memory_usage_mb', $this->translate('Active Memory'), 'vqs.guest_memory_usage_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->guest_memory_usage_mb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('host_memory_usage_mb', $this->translate('Host Memory'), 'vqs.host_memory_usage_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->host_memory_usage_mb);
                })->setSortExpression('vqs.host_memory_usage_mb')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('ballooned_memory_mb', 'Balloon', 'vqs.ballooned_memory_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->ballooned_memory_mb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('memory_usage', 'Memory Usage', $memoryColumns)
                ->setRenderer($memoryRenderer)
                ->setSortExpression('(vqs.guest_memory_usage_mb / vc.hardware_memorymb)')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('uptime', $this->translate('Uptime'), [
                'uptime' => 'vqs.uptime',
            ])->setRenderer(function ($row) {
                if ($row->uptime === null) {
                    return null;
                }

                return DateFormatter::formatDuration($row->uptime);
            }),
        ]);

        // $this->addPerfColumns();
    }

    protected function addPerfColumns()
    {
        $perf = new DelayedPerfdataRenderer($this->db());
        $this->addAvailableColumns([
            $perf->getDiskColumn()->setDefaultSortDirection('DESC'),
            $perf->getNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentDiskColumn()->setDefaultSortDirection('DESC'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'object_name',
            'cpu_usage',
            'memory_usage',
        ];
    }
}
