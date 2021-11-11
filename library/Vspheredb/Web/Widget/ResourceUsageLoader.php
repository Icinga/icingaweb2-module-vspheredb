<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\ZfDb\Adapter\Adapter;
use Ramsey\Uuid\UuidInterface;

class ResourceUsageLoader
{
    /** @var UuidInterface */
    protected $vCenterUuid;

    /** @var Adapter|\Zend_Db_Adapter_Abstract */
    protected $db;

    /**
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param UuidInterface|null $vCenterUuid
     * @return $this
     */
    public function filterVCenterUuid(UuidInterface $vCenterUuid = null)
    {
        $this->vCenterUuid = $vCenterUuid;

        return $this;
    }

    public function fetch()
    {
        $db = $this->db;
        $uuid = $this->vCenterUuid;
        $query = $db->select()->from(['h' => 'host_system'], [
            'used_mhz'  => 'SUM(hqs.overall_cpu_usage)',
            'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
            'used_mb'   => 'SUM(hqs.overall_memory_usage_mb)',
            'total_mb'  => 'SUM(h.hardware_memory_size_mb)',
        ])->join([
            'hqs' => 'host_quick_stats'
        ], 'h.uuid = hqs.uuid', []);
        if ($uuid) {
            $query->where('h.vcenter_uuid = ?', $uuid->getBytes());
        }
        $compute = $db->fetchRow($query);

        $query = $db->select()->from(['ds' => 'datastore'], [
            'ds_capacity'    => 'SUM(ds.capacity)',
            'ds_free_space'  => 'SUM(ds.free_space)',
            'ds_uncommitted' => 'SUM(ds.uncommitted)',
        ]);
        if ($uuid) {
            $query->where('ds.vcenter_uuid = ?', $uuid->getBytes());
        }
        $storage = $db->fetchRow($query);

        return ResourceUsage::fromSerialization((object) ((array) $compute + (array) $storage));
    }
}
