<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;
use Icinga\Util\Format;
use dipl\Html\Link;

class HostsTable extends ObjectsTable
{
    public function getColumnsToBeRendered()
    {
        return $this->getChosenTitles();
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            (new SimpleColumn('object_name', $this->translate('Name'), [
                'object_name' => 'o.object_name',
                'uuid'        => 'o.uuid',
            ]))->setRenderer(function ($row) {
                return Link::create(
                    $row->object_name,
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->uuid)]
                );
            }),
            new SimpleColumn('sysinfo_model', $this->translate('Model'), 'h.sysinfo_model'),
            (new SimpleColumn('cpu_usage', $this->translate('CPU Usage'), [
                'cpu_usage' => 'hqs.overall_cpu_usage',
                'cpu_total' => '(hardware_cpu_cores * hardware_cpu_mhz)',
            ]))->setRenderer(function ($row) {
                $title = sprintf('%s / %s MHz', $row->cpu_usage, $row->cpu_total);

                return new SimpleUsageBar($row->cpu_usage, $row->cpu_total, $title);
            })->setSortExpression(
                'hqs.overall_cpu_usage / (h.hardware_cpu_cores * h.hardware_cpu_mhz)'
            )->setDefaultSortDirection('DESC'),
            (new SimpleColumn('memory_usage', $this->translate('Memory Usage'), [
                'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
                'memory_usage_mb'         => 'hqs.overall_memory_usage_mb',
            ]))->setRenderer(function ($row) {
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
            (new SimpleColumn('cpu_cores', $this->translate('CPU Cores'), [
                // 'hardware_cpu_packages'   => 'h.hardware_cpu_packages',
                'hardware_cpu_cores'      => 'h.hardware_cpu_cores',
                'vms_cnt_cpu'             => 'vms.cnt_cpu',
            ]))->setRenderer(function ($row) {
                return sprintf('%d / %d', $row->hardware_cpu_cores, $row->vms_cnt_cpu);
            }),
            (new SimpleColumn('memory_size', $this->translate('Memory'), [
                'vms_memorymb'            => 'vms.memorymb',
                'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
            ]))->setRenderer(function ($row) {
                return sprintf(
                    '%s / %s',
                    Format::bytes($row->hardware_memory_size_mb * 1024 * 1024, Format::STANDARD_IEC),
                    Format::bytes($row->vms_memorymb * 1024 * 1024, Format::STANDARD_IEC)
                );
            }),
            new SimpleColumn('vms_cnt_cpu', $this->translate('VMs'), 'vms.cnt_cpu'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'object_name',
            'cpu_usage',
            'memory_usage',
            'vms_cnt_cpu',
        ];
    }

    public function prepareQuery()
    {
        $vms = $this->db()->select()->from(
            ['vc' => 'virtual_machine'],
            [
                'cnt'               => 'COUNT(*)',
                'cnt_cpu'           => 'SUM(vc.hardware_numcpu)',
                'memorymb'          => 'SUM(vc.hardware_memorymb)',
                'runtime_host_uuid' => 'vc.runtime_host_uuid',
            ]
        )->group('vc.runtime_host_uuid');

        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['h' => 'host_system'],
            'o.uuid = h.uuid',
            []
        )->join(
            ['hqs' => 'host_quick_stats'],
            'h.uuid = hqs.uuid',
            []
        )->joinLeft(
            ['vms' => $vms],
            'vms.runtime_host_uuid = h.uuid',
            []
        )->limit(100);

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $this->sortQuery($query, [
            'object_name',
            // 'memory_usage',
        ]);
    }

    protected function sortQuery(\Zend_Db_Select $query, $sortColumns)
    {
        foreach ($sortColumns as $columnName) {
            $sortColumn = $this->getAvailableColumn($columnName);
            $query->order(
                $sortColumn->getSortExpression()
                . ' ' . $sortColumn->getDefaultSortDirection()
            );
        }

        return $query;
    }
}
