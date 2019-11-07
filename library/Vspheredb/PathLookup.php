<?php

namespace Icinga\Module\Vspheredb;

use gipfl\IcingaWeb2\Link;

class PathLookup
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct(Db $db)
    {
        $this->db = $db->getDbAdapter();
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
            ->where('uuid = ?', $uuid);

        $row = $this->db->fetchRow($query);
        if ($row) {
            return Link::create(
                $row->object_name,
                $this->getBaseUrlByType($row->object_type),
                ['uuid' => bin2hex($uuid)],
                ['data-base-target' => '_next']
            );
        } else {
            return '-';
        }
    }

    protected function getBaseUrlByType($type)
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

    public function getObjectName($uuid)
    {
        $query = $this->db->select()
            ->from(['o' => 'object'], 'object_name')
            ->where('uuid = ?', $uuid);

        return $this->db->fetchOne($query);
    }

    public function getObjectNames($uuids)
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

    public function listFoldersBelongingTo($uuid)
    {
        return array_merge($this->listChildFoldersFor($uuid), [$uuid]);
    }

    public function listChildFoldersFor($uuid)
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

    protected function fetchChildFolderListFor($uuid)
    {
        $query = $this->db->select()->from('object', 'uuid')
            ->where('parent_uuid = ?', $uuid)
            ->where('object_type NOT IN (?)', ['HostSystem', 'VirtualMachine']);

        return $this->db->fetchCol($query);
    }

    public function listPathTo($uuid, $includeSelf = true)
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

    public function fetchParentForId($uuid)
    {
        $query = $this->db->select()
            ->from('object', 'parent_uuid')
            ->where('uuid = ?', $uuid);

        $parent = $this->db->fetchOne($query);
        if ($parent) {
            return $parent;
        } else {
            return null;
        }
    }
}
