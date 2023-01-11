<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class VmCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'vm_moref';

    protected $tagColumns = [
        'vm_uuid' => 'o.uuid',
        'vm_name' => 'o.object_name',
        'vm_guest_host_name' => 'vm.guest_host_name',
        'vm_moref' => 'o.moref',
    ];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                'nix' => '(NULL)',
            ]);
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['vm' => 'virtual_machine'], 'o.uuid = vm.uuid', [])
            ->where('vm.template = ?', 'n')
            ->order('vm.runtime_host_uuid')
            ->order('o.moref');

        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
