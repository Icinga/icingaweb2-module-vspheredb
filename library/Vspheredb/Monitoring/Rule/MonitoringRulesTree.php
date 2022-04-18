<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;

class MonitoringRulesTree
{
    use TranslationHelper;

    const ROOT_OBJECT_TYPE = 'root';

    /** @var Db */
    protected $db;

    /** @var string */
    protected $baseObjectFolderName;

    /** @var ?array */
    protected $fetchedTree;

    /** @var ?array */
    protected $configList;

    /** @var ?array */
    protected $allNodes;

    public function __construct(Db $db, $baseObjectFolderName)
    {
        $this->db = $db;
        $this->baseObjectFolderName = $baseObjectFolderName;
    }

    public function getBaseObjectFolderName(): string
    {
        return $this->baseObjectFolderName;
    }

    public function getNameForUuid($uuid): string
    {
        if ($uuid === MonitoringRuleSet::NO_OBJECT) {
            return $this->translate('All vCenters');
        }

        return $this->allNodes[$uuid]->object_name;
    }

    public function listParentUuidsFor($uuid): array
    {
        if ($this->allNodes === null) {
            $this->fetchTree();
        }

        if ($uuid === MonitoringRuleSet::NO_OBJECT) {
            return [];
        }

        $parents = [MonitoringRuleSet::NO_OBJECT];
        while (isset($this->allNodes[$uuid]->parent)) {
            $parents[] = $uuid = $this->allNodes[$uuid]->parent->uuid;
        }

        return $parents;
    }

    public function getRootNode()
    {
        return (object) [
            'object_name' => $this->translate('All vCenters'),
            'object_type' => self::ROOT_OBJECT_TYPE,
            'uuid'        => MonitoringRuleSet::NO_OBJECT,
            'children'    => $this->getTree()
        ];
    }

    public function hasConfigurationForUuid($uuid): bool
    {
        if ($this->configList === null) {
            $this->configList = $this->listAllConfiguredRuleSets();
        }

        return isset($this->configList[$uuid]);
    }

    public function getInheritedSettingsFor(BaseDbObject $object): InheritedSettings
    {
        $uuid = $object->object()->get('parent_uuid');
        $parents = $this->listParentUuidsFor($uuid);
        $parents[] = $uuid;

        return InheritedSettings::loadForUuids($parents, $this, $this->db);
    }

    /**
     * Hint: every DataCenter has 'datastore', 'host', 'network' and 'vm' as subfolders
     *
     * @return array
     */
    protected function fetchBaseObjectTypeFolders(): array
    {
        $db = $this->db->getDbAdapter();
        $query = "SELECT o.*, po.object_name, o.vcenter_uuid AS parent_uuid FROM object o"
            . ' JOIN object po on o.parent_uuid = po.uuid'
            . ' WHERE o.object_type = ? AND o.level = 2 AND o.object_name = ? ORDER BY po.object_name';

        return $db->fetchAll($query, ['Folder', $this->baseObjectFolderName]);
    }

    protected function fetchAllvCenters(): array
    {
        $db = $this->db->getDbAdapter();
        $query = "SELECT instance_uuid AS uuid, name AS object_name, 'vCenter' AS object_type, -1 AS level"
            . ' FROM vcenter ORDER BY name';

        return $db->fetchAll($query);
    }

    protected function fetchAllSubFolders(): array
    {
        $db = $this->db->getDbAdapter();
        $query = 'SELECT * FROM object'
            . ' WHERE object_type IN (?, ?, ?) AND level > 2 ORDER BY level, parent_uuid, object_name';

        return $db->fetchAll($query, [
            'ComputeResource',
            'ClusterComputeResource',
            'Folder'
        ]);
    }

    protected function listAllConfiguredRuleSets(): array
    {
        $db = $this->db->getDbAdapter();
        $query = $db->select()->from(MonitoringRuleSet::TABLE, [
            'k' => 'object_uuid',
            'v' => 'object_uuid'
        ])->where('object_folder = ?', $this->baseObjectFolderName);

        return $db->fetchPairs($query);
    }

    protected function getTree(): array
    {
        if ($this->fetchedTree === null) {
            $this->fetchedTree = $this->fetchTree();
        }

        return $this->fetchedTree;
    }

    protected function fetchTree(): array
    {
        $vCenters = $this->fetchAllvCenters();
        $baseFolders = $this->fetchBaseObjectTypeFolders();
        $allSubFolders = $this->fetchAllSubFolders();

        $all = [];
        $root = [];
        foreach ($vCenters as $folder) {
            $uuid = $folder->uuid;
            $folder->children = [];
            $root[$uuid] = $folder;
            $all[$uuid] = &$root[$uuid];
            $root[$uuid] = &$all[$uuid];
        }
        foreach (array_merge($baseFolders, $allSubFolders) as $folder) {
            $parent = $folder->parent_uuid;
            $uuid = $folder->uuid;
            if (isset($all[$parent])) {
                $folder->children = [];
                $folder->parent = &$all[$parent];
                $all[$uuid] = $folder;
                $all[$parent]->children[] = &$all[$uuid];
            }
        }
        $this->allNodes = $all;

        // 0 => rootfolders
        // 1 => datacenters
        // 2 => object folders

        return $root;
    }

    public function discard()
    {
        $this->allNodes = null;
        $this->fetchedTree = null;
    }

    public function __destruct()
    {
        $this->discard();
        $this->db = null;
    }
}
