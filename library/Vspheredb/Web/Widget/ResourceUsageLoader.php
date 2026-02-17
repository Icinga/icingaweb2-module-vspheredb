<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\PathLookup;
use Ramsey\Uuid\UuidInterface;
use Zend_Db_Adapter_Abstract;

class ResourceUsageLoader
{
    /** @var ?UuidInterface */
    protected ?UuidInterface $vCenterUuid = null;

    /** @var Adapter|Zend_Db_Adapter_Abstract */
    protected Adapter|Zend_Db_Adapter_Abstract $db;

    /** @var ?array */
    protected ?array $parentUuids = null;

    /**
     * @param Adapter|Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Adapter|Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    /**
     * @param UuidInterface|null $vCenterUuid
     * @return $this
     */
    public function filterVCenterUuid(?UuidInterface $vCenterUuid = null): static
    {
        $this->vCenterUuid = $vCenterUuid;

        return $this;
    }

    public function filterByParentUuid(string $uuid): static
    {
        $this->parentUuids = (new PathLookup($this->db))->listFoldersBelongingTo($uuid);

        return $this;
    }

    public function fetch(): ResourceUsage
    {
        $db = $this->db;
        $query = $db->select()
            ->from(['h' => 'host_system'], [
                'used_mhz'  => 'SUM(hqs.overall_cpu_usage)',
                'total_mhz' => 'SUM(h.hardware_cpu_cores * h.hardware_cpu_mhz)',
                'used_mb'   => 'SUM(hqs.overall_memory_usage_mb)',
                'total_mb'  => 'SUM(h.hardware_memory_size_mb)'
            ])
            ->join(['hqs' => 'host_quick_stats'], 'h.uuid = hqs.uuid', []);
        $compute = $db->fetchRow($this->applyFilters($query, 'h'));

        $query = $db->select()->from(['ds' => 'datastore'], [
            'ds_capacity'    => 'SUM(ds.capacity)',
            'ds_free_space'  => 'SUM(ds.free_space)',
            'ds_uncommitted' => 'SUM(ds.uncommitted)'
        ]);
        $storage = $db->fetchRow($this->applyFilters($query, 'ds'));

        return ResourceUsage::fromSerialization((object) ((array) $compute + (array) $storage));
    }

    protected function applyFilters($query, $alias)
    {
        if ($this->vCenterUuid) {
            $query->where("$alias.vcenter_uuid = ?", $this->vCenterUuid->getBytes());
        }
        if ($this->parentUuids) {
            $query
                ->join(['o' => 'object'], "o.uuid = $alias.uuid", [])
                ->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }
}
