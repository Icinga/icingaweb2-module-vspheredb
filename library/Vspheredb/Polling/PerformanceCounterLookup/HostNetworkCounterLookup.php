<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;
use Zend_Db_Select;

class HostNetworkCounterLookup extends DefaultCounterLookup
{
    protected ?string $objectKey = 'host_moref';

    protected ?string $instanceKey = 'device_label';

    protected ?array $tagColumns = [
        'host_uuid'    => 'o.uuid',
        'sysinfo_uuid' => 'hs.sysinfo_uuid',
        'host_moref'   => 'o.moref',
        'host_name'    => 'o.object_name',
        // 'pnic_key'     => 'hpn.nic_key', -> key-vim.host.PhysicalNic-vmnic0, ugly
        'device_label' => 'hpn.device'
    ];

    protected function prepareInstancesQuery(?UuidInterface $vCenterUuid = null): Zend_Db_Select
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                'device' => "GROUP_CONCAT(hpn.device SEPARATOR ',')"
            ])
            ->group('hs.uuid')
            ->order('hs.uuid')
            ->order('hpn.device');
    }

    protected function prepareBaseQuery(?UuidInterface $vCenterUuid = null): Zend_Db_Select
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['hs' => 'host_system'], 'o.uuid = hs.uuid', [])
            ->join(['hpn' => 'host_physical_nic'], 'hpn.host_uuid = hs.uuid', []);
        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
