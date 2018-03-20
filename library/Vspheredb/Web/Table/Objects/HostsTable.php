<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Web\Widget\ServiceTagRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;
use Icinga\Util\Format;

class HostsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/host';

    protected function initialize()
    {
        $serviceTagRenderer = new ServiceTagRenderer();
        $powerStateRenderer = new PowerStateRenderer();
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createColumn('runtime_power_state', $this->translate('Power'), 'h.runtime_power_state')
                ->setRenderer($powerStateRenderer),
            $this->createObjectNameColumn(),
            $this->createColumn('sysinfo_vendor', $this->translate('Vendor'), 'h.sysinfo_vendor'),
            $this->createColumn('sysinfo_model', $this->translate('Model'), 'h.sysinfo_model'),
            $this->createColumn('bios_version', $this->translate('BIOS Version'), 'h.bios_version'),
            $this->createColumn('bios_release_date', $this->translate('BIOS Release Date'), 'h.bios_release_date')
                ->setRenderer(function ($row) {
                    return DateFormatter::formatDate(strtotime($row->bios_release_date));
                }),
            $this->createColumn('service_tag', $this->translate('Service Tag'), [
                'service_tag'    => 'h.service_tag',
                'sysinfo_vendor' => 'h.sysinfo_vendor',
            ])->setRenderer($serviceTagRenderer),
            $this->createColumn('cpu_usage', $this->translate('CPU Usage'), [
                'cpu_usage' => 'hqs.overall_cpu_usage',
                'cpu_total' => '(hardware_cpu_cores * hardware_cpu_mhz)',
            ])->setRenderer(function ($row) {
                $title = sprintf('%s / %s MHz', $row->cpu_usage, $row->cpu_total);

                return new SimpleUsageBar($row->cpu_usage, $row->cpu_total, $title);
            })->setSortExpression(
                'hqs.overall_cpu_usage / (h.hardware_cpu_cores * h.hardware_cpu_mhz)'
            )->setDefaultSortDirection('DESC'),
            $this->createColumn('memory_usage', $this->translate('Memory Usage'), [
                'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
                'memory_usage_mb'         => 'hqs.overall_memory_usage_mb',
            ])->setRenderer(function ($row) {
                $used = $row->memory_usage_mb * 1024 * 1024;
                $total = $row->hardware_memory_size_mb * 1024 * 1024;
                $title = sprintf(
                    '%s / %s',
                    Format::bytes($used),
                    Format::bytes($total)
                );

                return new SimpleUsageBar($used, $total, $title);
            })->setSortExpression(
                '(hqs.overall_memory_usage_mb / h.hardware_memory_size_mb)'
            )->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_cpu_cores', $this->translate('CPU Cores'), 'h.hardware_cpu_cores')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('vms_cnt_cpu', $this->translate('VM CPUs'), 'vms.cnt_cpu')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memory_size_mb', $this->translate('Memory'), 'h.hardware_memory_size_mb')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->hardware_memory_size_mb * 1024 * 1024, Format::STANDARD_IEC);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('vms_memorymb', $this->translate('VMs Memory'), 'vms.memorymb')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->vms_memorymb * 1024 * 1024, Format::STANDARD_IEC);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('vms_cnt', $this->translate('VMs'), 'vms.cnt')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('spectre_meltdown', $this->translate('Spectre / Meltdown'), [
                'sysinfo_vendor'    => 'h.sysinfo_vendor',
                'sysinfo_model'     => 'h.sysinfo_model',
                'bios_version'      => 'h.bios_version',
                'bios_release_date' => 'h.bios_release_date',
            ])->setRenderer(function ($row) {
                $host = HostSystem::create([
                    'sysinfo_vendor'    => $row->sysinfo_vendor,
                    'sysinfo_model'     => $row->sysinfo_model,
                    'bios_version'      => $row->bios_version,
                    'bios_release_date' => $row->bios_release_date,
                ]);

                return new SpectreMelddownBiosInfo($host);
            }),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'runtime_power_state',
            'object_name',
            'cpu_usage',
            'memory_usage',
        ];
    }

    protected function createVmSubQuery()
    {
        return $this->db()->select()->from(
            ['vc' => 'virtual_machine'],
            [
                'cnt'               => 'COUNT(*)',
                'cnt_cpu'           => 'SUM(vc.hardware_numcpu)',
                'memorymb'          => 'SUM(vc.hardware_memorymb)',
                'runtime_host_uuid' => 'vc.runtime_host_uuid',
            ]
        )->group('vc.runtime_host_uuid');
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $wantsVms = false;
        foreach ($columns as $column) {
            if (substr($column, 0, 4) === 'vms.') {
                $wantsVms = true;
                break;
            }
        }

        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['h' => 'host_system'],
            'o.uuid = h.uuid',
            []
        )->join(
            ['hqs' => 'host_quick_stats'],
            'h.uuid = hqs.uuid',
            []
        )->limit(100);

        if ($wantsVms) {
            $query->joinLeft(
                ['vms' => $this->createVmSubQuery()],
                'vms.runtime_host_uuid = h.uuid',
                []
            );
        }

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }
}
