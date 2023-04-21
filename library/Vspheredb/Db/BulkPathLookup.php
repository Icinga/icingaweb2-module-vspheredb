<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Module\Vspheredb\Db;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class BulkPathLookup
{
    /** @var Db */
    protected $db;

    protected $nodes;

    /** @var ?array */
    protected $vCenterFilterUuids;

    public function __construct(Db $db, ?array $vCenterUuids = null)
    {
        $this->db = $db;
        $this->vCenterFilterUuids = $vCenterUuids;
    }

    public function getParents(?string $objectParent): array
    {
        if ($this->nodes === null) {
            $this->nodes = $this->fetchAllParents();
        }
        $path = [];
        $parentUuid = $objectParent;
        while ($parentUuid !== null) {
            if (! isset($this->nodes[$parentUuid])) {
                throw new RuntimeException('Parent lookup failed: ' . Uuid::fromBytes($parentUuid)->toString());
            }
            $node = $this->nodes[$parentUuid];
            $path[$parentUuid] = $node->object_name;
            $parentUuid = $node->parent_uuid;
        }

        return array_reverse($path, true);
    }

    protected function fetchAllParents(): array
    {
        $db = $this->db->getDbAdapter();
        $query = $db->select()->from(['p' => 'object'], [
            'uuid'        => 'p.uuid',
            'parent_uuid' => 'p.parent_uuid',
            'object_name' => 'p.object_name'
        ])->join(['o' => 'object'], 'o.parent_uuid = p.uuid', []);
        QueryHelper::applyOptionalVCenterFilter($db, $query, 'o.vcenter_uuid', $this->vCenterFilterUuids);
        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            $result[$row->uuid] = $row;
        }
        return $result;
    }
}
