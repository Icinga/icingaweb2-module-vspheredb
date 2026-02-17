<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use gipfl\ZfDb\Select;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\BiosInfo;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Web\Widget\ServiceTagRenderer;
use Icinga\Module\Vspheredb\Format;
use Zend_Db_Select;

class HostsTable extends ObjectsTable
{
    protected ?string $baseUrl = 'vspheredb/host';

    protected function initialize(): void
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

            $this->createColumn('vcenter_name', $this->translate('vCenter / ESXi'), 'vc.name'),

            $this->createColumn('bios_version', $this->translate('BIOS Version'), 'h.bios_version'),

            $this->createColumn('bios_release_date', $this->translate('BIOS Release Date'), 'h.bios_release_date')
                ->setRenderer(fn($row) => DateFormatter::formatDate(strtotime($row->bios_release_date))),

            $this->createColumn('service_tag', $this->translate('Service Tag'), [
                'service_tag'    => 'h.service_tag',
                'sysinfo_vendor' => 'h.sysinfo_vendor'
            ])
                ->setRenderer($serviceTagRenderer),

            $this->createColumn('product_api_version', $this->translate('API Version'), [
                'product_api_version' => 'h.product_api_version'
            ]),

            $this->createColumn('cpu_usage', $this->translate('CPU Usage'), [
                'cpu_usage' => 'hqs.overall_cpu_usage',
                'cpu_total' => '(hardware_cpu_cores * hardware_cpu_mhz)'
            ])
                ->setRenderer(fn($row) => new CpuUsage($row->cpu_usage, $row->cpu_total))
                ->setSortExpression('hqs.overall_cpu_usage / (h.hardware_cpu_cores * h.hardware_cpu_mhz)')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('memory_usage', $this->translate('Memory Usage'), [
                'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
                'memory_usage_mb'         => 'hqs.overall_memory_usage_mb'
            ])
                ->setRenderer(fn($row) => new MemoryUsage($row->memory_usage_mb, $row->hardware_memory_size_mb))
                ->setSortExpression('(hqs.overall_memory_usage_mb / h.hardware_memory_size_mb)')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_cpu_cores', $this->translate('CPU Cores'), 'h.hardware_cpu_cores')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('vms_overall_status', $this->translate('VM Status'), [
                'vms_cnt_overall_gray'   => 'vms.vms_cnt_overall_gray',
                'vms_cnt_overall_green'  => 'vms.vms_cnt_overall_green',
                'vms_cnt_overall_yellow' => 'vms.vms_cnt_overall_yellow',
                'vms_cnt_overall_red'    => 'vms.vms_cnt_overall_red'
            ])
                ->setRenderer(function ($row) {
                    $result = [];
                    foreach (['red', 'yellow', 'gray', 'green'] as $state) {
                        $column = "vms_cnt_overall_$state";
                        if ($row->$column > 0) {
                            $result[] = Link::create(
                                $row->$column,
                                'vspheredb/host/vms',
                                [
                                    'uuid'           => Util::niceUuid($row->uuid),
                                    'overall_status' => $state
                                ],
                                ['class' => ['state', $state]]
                            );
                        }
                    }

                    if (empty($result)) {
                        return '-';
                    }

                    return $result;
                })
                ->setSortExpression([
                    'vms.vms_cnt_overall_red',
                    'vms.vms_cnt_overall_yellow',
                    'vms.vms_cnt_overall_gray',
                    'vms.vms_cnt_overall_green'
                ])
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('vms_cnt_cpu', $this->translate('VM CPUs'), 'vms.cnt_cpu')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn(
                'pcpu_vcpu_ration',
                $this->translate('vCPU/pCPU'),
                '(vms.cnt_cpu / h.hardware_cpu_cores)'
            )
                ->setRenderer(fn($row) => sprintf('%.3g:1', $row->pcpu_vcpu_ration))
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_memory_size_mb', $this->translate('Memory'), 'h.hardware_memory_size_mb')
                ->setRenderer(fn($row) => Format::mBytes($row->hardware_memory_size_mb))
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('vms_memorymb', $this->translate('VMs Memory'), 'vms.memorymb')
                ->setRenderer(fn($row) => Format::mBytes($row->vms_memorymb))
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('vms_cnt', $this->translate('VMs'), 'vms.cnt')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('spectre_meltdown', $this->translate('Spectre / Meltdown'), [
                'sysinfo_vendor'    => 'h.sysinfo_vendor',
                'sysinfo_model'     => 'h.sysinfo_model',
                'bios_version'      => 'h.bios_version',
                'bios_release_date' => 'h.bios_release_date'
            ])
                ->setRenderer(fn($row) => new BiosInfo(HostSystem::create([
                    'sysinfo_vendor'    => $row->sysinfo_vendor,
                    'sysinfo_model'     => $row->sysinfo_model,
                    'bios_version'      => $row->bios_version,
                    'bios_release_date' => $row->bios_release_date
                ]))),

            $this->createColumn('uptime', $this->translate('Uptime'), ['uptime' => 'hqs.uptime'])
                ->setRenderer(fn($row) => $row->uptime === null ? null : DateFormatter::formatDuration($row->uptime))
        ]);
    }

    public function getDefaultColumnNames(): array
    {
        return [
            'object_name',
            'cpu_usage',
            'memory_usage'
        ];
    }

    protected function createVmSubQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()
            ->from(['vc' => 'virtual_machine'], [
                'cnt'                    => 'COUNT(*)',
                'cnt_cpu'                => 'SUM(vc.hardware_numcpu)',
                'memorymb'               => 'SUM(vc.hardware_memorymb)',
                'runtime_host_uuid'      => 'vc.runtime_host_uuid',
                'vms_cnt_overall_gray'   => "SUM(CASE WHEN vo.overall_status = 'gray' THEN 1 ELSE 0 END)",
                'vms_cnt_overall_green'  => "SUM(CASE WHEN vo.overall_status = 'green' THEN 1 ELSE 0 END)",
                'vms_cnt_overall_yellow' => "SUM(CASE WHEN vo.overall_status = 'yellow' THEN 1 ELSE 0 END)",
                'vms_cnt_overall_red'    => "SUM(CASE WHEN vo.overall_status = 'red' THEN 1 ELSE 0 END)"
            ])
            ->join(['vo' => 'object'], 'vo.uuid = vc.uuid', [])
            ->group('vc.runtime_host_uuid');
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        $columns = $this->getRequiredDbColumns();
        $wantsVms = false;
        $wantsVCenter = false;
        foreach ($columns as $column) {
            if (str_starts_with($column, 'vms.') || str_starts_with($column, '(vms.')) {
                $wantsVms = true;
                break;
            }
            if (str_starts_with($column, 'vc.')) {
                $wantsVCenter = true;
            }
        }

        $query = $this->db()->select()
            ->from(['o' => 'object'], $columns)
            ->join(['h' => 'host_system'], 'o.uuid = h.uuid', [])
            ->joinLeft(['hqs' => 'host_quick_stats'], 'h.uuid = hqs.uuid', []);

        if ($wantsVms) {
            $query->joinLeft(['vms' => $this->createVmSubQuery()], 'vms.runtime_host_uuid = h.uuid', []);
        }
        if ($wantsVCenter) {
            $query->join(['vc' => 'vcenter'], 'vc.instance_uuid = h.vcenter_uuid', []);
        }

        return $query;
    }
}
