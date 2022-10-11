<?php

namespace Icinga\Module\Vspheredb\DbObject;

class ComputeCluster extends BaseDbObject
{
    protected $keyName = 'uuid';

    // TODO: protected $table = 'compute_cluster';
    protected $table = 'object';

    protected $defaultProperties = [
        'uuid'           => null,
        'vcenter_uuid'   => null,
        'moref'          => null,
        'object_name'    => null,
        'object_type'    => null,
        'overall_status' => null,
        'level'          => null,
        'parent_uuid'    => null,
        'tags'           => null,
    ];

    protected $propertyMap = [
    ];

    public function calculateStats()
    {
        $db = $this->getDb();
        return $db->fetchRow(
            $db->select()
                ->from(['o' => 'object'], [
                    'hardware_memory_size_mb' => 'SUM(hs.hardware_memory_size_mb)',
                    'hardware_cpu_mhz'        => 'SUM(hs.hardware_cpu_mhz)',
                    'hardware_cpu_cores'      => 'SUM(hs.hardware_cpu_cores)',
                    'overall_cpu_usage'       => 'SUM(hqs.overall_cpu_usage)',
                    'overall_memory_usage_mb' => 'SUM(hqs.overall_memory_usage_mb)'
                ])
                ->join(['hs'  => 'host_system'], 'hs.uuid = o.uuid', [])
                ->join(['hqs' => 'host_quick_stats'], 'hqs.uuid = hs.uuid', [])
                ->where('o.parent_uuid = ?', $this->get('uuid'))
                ->where('o.object_type = ?', 'HostSystem')
        );
    }

    public function countHosts()
    {
        $db = $this->getDb();
        return $db->fetchOne(
            $db->select()
                ->from('object', 'COUNT(*)')
                ->where('parent_uuid = ?', $this->get('uuid'))
                ->where('object_type = ?', 'HostSystem')
        );
    }
}
