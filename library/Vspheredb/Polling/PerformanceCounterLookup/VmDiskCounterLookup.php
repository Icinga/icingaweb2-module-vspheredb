<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class VmDiskCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'vm_moref';
    protected $instanceKey = 'disk_hardware_key';

    protected $tagColumns = [
        'vm_uuid' => 'LOWER(HEX(o.uuid))',
        'vm_name' => 'o.object_name',
        'vm_guest_host_name' => 'vm.guest_host_name',
        'vm_moref' => 'o.moref',
        'disk_hardware_key' => "(CASE WHEN vmhw.label LIKE 'IDE %' THEN 'ide' ELSE 'scsi' END"
            . " || vmhc.bus_number || ':' || vmhw.unit_number)",
        'disk_hardware_label' => 'vmhw.label',
    ];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                "GROUP_CONCAT(CASE WHEN vmhw.label LIKE 'IDE %' THEN 'ide' ELSE 'scsi' END"
                . " || vmhc.bus_number || ':' || vmhw.unit_number SEPARATOR ',')",
            ])
            ->group('vm.uuid');
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['vm' => 'virtual_machine'], 'o.uuid = vm.uuid', [])
            ->join(['vmd' => 'vm_disk'], 'vm.uuid = vmd.vm_uuid', [])
            ->join(
                ['vmhw' => 'vm_hardware'],
                'vmd.vm_uuid = vmhw.vm_uuid AND vmd.hardware_key = vmhw.hardware_key',
                []
            )
            ->join(
                ['vmhc' => 'vm_hardware'],
                'vmhw.vm_uuid = vmhc.vm_uuid AND vmhw.controller_key = vmhc.hardware_key',
                []
            )
            ->where("vmhc.label LIKE 'SCSI controller %' OR vmhc.label LIKE 'IDE %'")
            ->order('vm.runtime_host_uuid')
            ->order('vmd.hardware_key');

        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
