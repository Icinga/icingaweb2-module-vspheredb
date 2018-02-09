<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;
use dipl\Html\Link;

class HostsBiosTable extends ObjectsTable
{
    protected $searchColumns = [
        'object_name',
        'sysinfo_vendor',
        'sysinfo_model',
        'bios_version',
    ];

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Name'),
            $this->translate('Model'),
            $this->translate('Bios'),
        ];
    }

    public function renderRow($row)
    {
        $caption = Link::create(
            $row->object_name,
            'vspheredb/host',
            ['uuid' => bin2hex($row->uuid)]
        );
        $host = HostSystem::create((array) [
            'sysinfo_vendor'    => $row->sysinfo_vendor,
            'sysinfo_model'     => $row->sysinfo_model,
            'bios_version'      => $row->bios_version,
            'bios_release_date' => $row->bios_release_date,
        ]);
        $tr = $this::row([
            $caption,
            [$row->sysinfo_vendor, ' ', $row->sysinfo_model],
            new SpectreMelddownBiosInfo($host)
        ]);
        $tr->attributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
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
            [
                'uuid'                    => 'o.uuid',
                'object_name'             => 'o.object_name',
                'overall_status'          => 'o.overall_status',
                'sysinfo_vendor'          => 'h.sysinfo_vendor',
                'sysinfo_model'           => 'h.sysinfo_model',
                'bios_version'            => 'h.bios_version',
                'bios_release_date'       => 'h.bios_release_date',
                'hardware_cpu_packages'   => 'h.hardware_cpu_packages',
                'hardware_cpu_cores'      => 'h.hardware_cpu_cores',
                'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
                'runtime_power_state'     => 'h.runtime_power_state',
                'running_vms'             => 'vms.cnt',
                'vms_cnt_cpu'             => 'vms.cnt_cpu',
                'vms_memorymb'            => 'vms.memorymb'
            ]
        )->join(
            ['h' => 'host_system'],
            'o.uuid = h.uuid',
            []
        )->joinLeft(
            ['vms' => $vms],
            'vms.runtime_host_uuid = h.uuid',
            []
        )->order('object_name ASC');
        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }
}
