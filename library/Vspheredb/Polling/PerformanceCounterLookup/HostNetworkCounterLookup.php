<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class HostNetworkCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'host_moref';
    protected $instanceKey = 'device_label';

    protected $tagColumns = [
        'host_moref'   => 'o.moref',
        'host_name'    => 'o.object_name',
        'pnic_key'     => 'hpn.nic_key',
        'device_label' => 'hpn.device',
    ];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                'device' => "GROUP_CONCAT(hpn.device SEPARATOR ',')",
            ])
            ->group('hs.uuid')
            ->order('hs.uuid')
            ->order('hpn.device');
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
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
