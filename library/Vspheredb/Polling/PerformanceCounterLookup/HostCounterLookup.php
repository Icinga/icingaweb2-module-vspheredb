<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class HostCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'host_moref';

    protected $tagColumns = [
        'host_uuid'    => 'o.uuid',
        'sysinfo_uuid' => 'hs.sysinfo_uuid',
        'host_moref'   => 'o.moref',
        'host_name'    => 'o.object_name',
    ];

    protected function prepareInstancesQuery(?UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                'nix' => '(NULL)',
            ]);
    }

    protected function prepareBaseQuery(?UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['hs' => 'host_system'], 'o.uuid = hs.uuid', [])
            ->order('o.moref');
        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
