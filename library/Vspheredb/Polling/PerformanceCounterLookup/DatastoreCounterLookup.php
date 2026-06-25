<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class DatastoreCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'ds_moref';

    protected $tagColumns = [
        'ds_uuid'  => 'o.uuid',
        'ds_moref' => 'o.moref',
        'ds_name'  => 'o.object_name'
    ];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
                // Applying the same workaround as in HostCounterLookup to avoid exceptions in our code.
                // Ideally, we would address the underlying issue, but resolving it isn't worth the effort right now.
                'nix' => '(NULL)'
            ]);
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['ds' => 'datastore'], 'o.uuid = ds.uuid', []);

        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
