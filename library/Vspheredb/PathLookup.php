<?php

namespace Icinga\Module\Vspheredb;

use dipl\Html\Link;

class PathLookup
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct(Db $db)
    {
        $this->db = $db->getDbAdapter();
    }

    public function linkToObject($id)
    {
        if (empty($id)) {
            return '-';
        }

        $query = $this->db->select()
            ->from(['o' => 'object'], ['object_name', 'object_type'])
            ->where('id = ?', (int) $id);

        $row = $this->db->fetchRow($query);
        if ($row) {
            return Link::create(
                $row->object_name,
                $this->getBaseUrlByType($row->object_type),
                ['id' => $id],
                ['data-base-target' => '_next']
            );
        } else {
            return '-';
        }
    }

    protected function getBaseUrlByType($type)
    {
        switch ($type) {
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

    public function getObjectName($id)
    {
        $query = $this->db->select()
            ->from(['o' => 'object'], 'object_name')
            ->where('id = ?', (int) $id);

        return $this->db->fetchOne($query);
    }

    public function getObjectNames($ids)
    {
        $query = $this->db->select()
            ->from(['o' => 'object'], ['id', 'object_name'])
            ->where('id IN (?)', $ids)
            ->order('level');

        return $this->db->fetchPairs($query);
    }

    public function listFoldersBelongingTo($id)
    {
        return array_merge($this->listChildFoldersFor($id), [$id]);
    }

    public function listChildFoldersFor($id)
    {
        $folders = [];
        $pid = $id;
        foreach ($this->fetchChildFolderListFor($pid) as $pid) {
            $folders[] = $pid;
            foreach ($this->listChildFoldersFor($pid) as $child) {
                $folders[] = $child;
            }
        }

        return $folders;
    }

    protected function fetchChildFolderListFor($id)
    {
        $query = $this->db->select()->from('object', 'id')
            ->where('parent_id = ?', $id)
            ->where('object_type NOT IN (?)', ['HostSystem', 'VirtualMachine']);

        return $this->db->fetchCol($query);
    }

    public function listPathTo($id, $includeSelf = true)
    {
        if ($includeSelf) {
            $parents = [$id];
        } else {
            $parents = [];
        }

        $pid = $id;
        while ($pid = $this->fetchParentForId($pid)) {
            $parents[] = $pid;
        }

        return $parents;
    }

    public function fetchParentForId($id)
    {
        $query = $this->db->select()
            ->from('object', 'parent_id')
            ->where('id = ?', $id);

        $parent = $this->db->fetchOne($query);
        if ($parent) {
            return (int) $parent;
        } else {
            return null;
        }
    }
}
