<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class VmNetworkCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'vm_moref';
    protected $instanceKey = 'interface_hardware_key';
    protected $tagColumns = [
        'vm_uuid'                => 'LOWER(HEX(o.uuid))',
        'vm_moref'               => 'o.moref',
        'vm_name'                => 'o.object_name',
        'vm_guest_host_name'     => "COALESCE(vm.guest_host_name, '(null)')",
        'interface_hardware_key' => 'vna.hardware_key',
        // 'parent_name'     => 'po.object_name',
        'interface_label'        => 'vh.label',
        // 'portgroup_name'  => 'pgo.object_name',
    ];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                'hardware_key' => "GROUP_CONCAT(vna.hardware_key SEPARATOR ',')",
            ])
            ->group('vm.uuid')
            ->order('vm.runtime_host_uuid')
            ->order('vna.hardware_key');
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['vm' => 'virtual_machine'], 'o.uuid = vm.uuid', [])
            ->join(['vna' => 'vm_network_adapter'], 'vna.vm_uuid = vm.uuid', [])
            // Required for tags, not for instances:
            ->join(['vh' => 'vm_hardware'], 'vh.vm_uuid = vna.vm_uuid AND vh.hardware_key = vna.hardware_key', []);

        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
