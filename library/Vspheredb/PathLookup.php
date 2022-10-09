<?php

namespace Icinga\Module\Vspheredb;

use gipfl\IcingaWeb2\Link;
use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Ramsey\Uuid\Uuid;

class PathLookup
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /**
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param $uuid
     * @return string|Link
     */
    public function linkToObject($uuid)
    {
        if (empty($uuid)) {
            return '-';
        }

        $query = $this->db->select()
            ->from(['o' => 'object'], ['object_name', 'object_type'])
            ->where('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $this->db));

        $row = $this->db->fetchRow($query);
        if ($row) {
            return Link::create(
                $row->object_name,
                $this->getBaseUrlByType($row->object_type),
                ['uuid' => Uuid::fromBytes($uuid)->toString()],
                ['data-base-target' => '_next']
            );
        } else {
            return '-';
        }
    }

    protected function getBaseUrlByType($type): string
    {
        switch ($type) {
            case 'Datastore':
                return 'vspheredb/datastore';
            case 'HostSystem':
                return 'vspheredb/host';
            case 'VirtualMachine':
                return 'vspheredb/vm';
            case 'ClusterComputeResource':
            case 'ComputeResource':
                return 'vspheredb/hosts';
            case 'Datacenter':
            case 'Folder':
            default:
                return 'vspheredb/vms';
        }
    }

    public function getObjectName($uuid): string
    {
        $query = $this->db->select()
            ->from(['o' => 'object'], 'object_name')
            ->where('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $this->db));

        return $this->db->fetchOne($query);
    }

    public function getObjectNames($uuids): array
    {
        if (empty($uuids)) {
            return [];
        }

        $query = $this->db->select()
            ->from(['o' => 'object'], ['uuid', 'object_name'])
            ->where('uuid IN (?)', $uuids)
            ->order('level');

        return $this->db->fetchPairs($query);
    }

    public function listFoldersBelongingTo($uuid): array
    {
        return array_merge($this->listChildFoldersFor($uuid), [$uuid]);
    }

    public function listChildFoldersFor($uuid): array
    {
        $folders = [];
        $puuid = $uuid;
        foreach ($this->fetchChildFolderListFor($puuid) as $puuid) {
            $folders[] = $puuid;
            foreach ($this->listChildFoldersFor($puuid) as $child) {
                $folders[] = $child;
            }
        }

        return $folders;
    }

    protected function fetchChildFolderListFor($uuid): array
    {
        $query = $this->db->select()->from('object', 'uuid')
            ->where('parent_uuid = ?', DbUtil::quoteBinaryCompat($uuid, $this->db))
            ->where('object_type NOT IN (?)', ['HostSystem', 'VirtualMachine']);

        return $this->db->fetchCol($query);
    }

    public function listPathTo($uuid, $includeSelf = true): array
    {
        if ($includeSelf) {
            $parents = [$uuid];
        } else {
            $parents = [];
        }

        $puuid = $uuid;
        while ($puuid = $this->fetchParentForId($puuid)) {
            $parents[] = $puuid;
        }

        return $parents;
    }

    public function fetchParentForId($uuid): ?string
    {
        $query = $this->db->select()
            ->from('object', 'parent_uuid')
            ->where('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $this->db));

        $parent = $this->db->fetchOne($query);
        if ($parent) {
            return $parent;
        } else {
            return null;
        }
    }
}
