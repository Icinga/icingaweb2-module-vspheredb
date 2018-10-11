<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Widget\DelayedPerfdataRenderer;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;
use Icinga\Util\Format;

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

        return $query;
    }

    protected function initialize()
    {
        $perf = new DelayedPerfdataRenderer($this->db());
        $powerStateRenderer = new PowerStateRenderer();
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createColumn('runtime_power_state', $this->translate('Power'), 'vc.runtime_power_state')
                ->setRenderer($powerStateRenderer),
            $this->createObjectNameColumn(),
            $this->createColumn('host_name', 'Host', 'h.host_name'),
            $this->createColumn('guest_ip_address', $this->translate('Guest IP'), 'vc.guest_ip_address'),
            $perf->getDiskColumn()->setDefaultSortDirection('DESC'),
            $perf->getNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentDiskColumn()->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_numcpu', 'CPUs', 'vc.hardware_numcpu')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memorymb', 'Memory', 'vc.hardware_memorymb')
                ->setRenderer(function ($row) {
                    return $this->formatMb($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('ballooned_memory_mb', 'Balloon', 'vqs.ballooned_memory_mb')
                ->setRenderer(function ($row) {
                    return $this->formatMb($row->ballooned_memory_mb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('guest_memory_usage_mb', 'Memory Usage', [
                'guest_memory_usage_mb' => 'vqs.guest_memory_usage_mb',
                'hardware_memorymb'     => 'vc.hardware_memorymb',
            ])->setRenderer(function ($row) {
                $used = $row->guest_memory_usage_mb * 1024 * 1024;
                $total = $row->hardware_memorymb * 1024 * 1024;
                $title = sprintf(
                    '%s / %s',
                    Format::bytes($used),
                    Format::bytes($total)
                );

                return new SimpleUsageBar($used, $total, $title);
            })->setSortExpression(
                '(vqs.guest_memory_usage_mb / vc.hardware_memorymb)'
            )->setDefaultSortDirection('DESC'),


            $this->createColumn('uptime', $this->translate('Uptime'), [
                'uptime' => 'vqs.uptime',
            ])->setRenderer(function ($row) {
                if ($row->uptime === null) {
                    return null;
                }

                return DateFormatter::formatDuration($row->uptime);
            }),
            $this->createColumn('host_memory_usage_mb', 'Host Memory Usage', [
                'host_memory_usage_mb' => 'vqs.host_memory_usage_mb',
                'hardware_memorymb'     => 'vc.hardware_memorymb',
            ])->setRenderer(function ($row) {
                $used = $row->host_memory_usage_mb * 1024 * 1024;
                $total = $row->hardware_memorymb * 1024 * 1024;
                $title = sprintf(
                    '%s / %s',
                    Format::bytes($used),
                    Format::bytes($total)
                );

                return new SimpleUsageBar($used, $total, $title);
            })->setSortExpression(
                '(vqs.host_memory_usage_mb / vc.hardware_memorymb)'
            )->setDefaultSortDirection('DESC'),


        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'runtime_power_state',
            'object_name',
            'hardware_numcpu',
            'hardware_memorymb'
        ];
    }
}
