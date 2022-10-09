<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRuleSet;
use Icinga\Module\Vspheredb\Util;

class PersistedRuleProblems
{
    protected const TABLE = 'monitoring_rule_problem';

    /** @var Db */
    protected $db;

    protected $currentState;

    protected $checked;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function refresh()
    {
        $this->currentState = $this->fetchCurrentProblems();
        MonitoringRuleSet::preloadAll($this->db);
        $objects = ManagedObject::loadAll($this->db, null, 'uuid');
        $datastores = Datastore::loadAll($this->db, null, 'uuid');
        $this->presetManagedObjects($datastores, $objects);
        $this->checkObjects($datastores, 'datastore');
        unset($datastores);

        HostQuickStats::preloadAll($this->db);
        $hosts = HostSystem::loadAll($this->db, null, 'uuid');
        $this->presetManagedObjects($hosts, $objects);
        $this->checkObjects($hosts, 'host');

        VmQuickStats::preloadAll($this->db);
        // TODO: Preload Disk Usage and Snapshots
        $vmQuery = $this->db->getDbAdapter()->select()->from('virtual_machine')->where('template = ?', 'n');
        $vms = VirtualMachine::loadAll($this->db, $vmQuery, 'uuid');
        foreach ($vms as $vm) {
            if ($vm->get('template') === 'y') {
                continue;
            }
            if ($uuid = $vm->get('runtime_host_uuid')) {
                if (isset($hosts[$uuid])) {
                    $vm->setRuntimeHost($hosts[$uuid]);
                }
            }
            $uuid = $vm->get('uuid');
            // Logic duplicates checkObjects, but this saves one iteration
            if (isset($objects[$uuid])) {
                $vm->setManagedObject($objects[$uuid]);
            }
        }
        $this->checkObjects($vms, 'vm');

        unset($objects);
        unset($hosts);
        unset($vms);
        HostQuickStats::clearPreloadCache();
        MonitoringRuleSet::clearPreloadCache();
        VmQuickStats::clearPreloadCache();
        $this->dropObsoleteRows();
        $this->currentState = null;
    }

    /**
     * @param BaseDbObject[] $objects
     * @param ManagedObject[] $managedObjects
     * @return void
     */
    protected function presetManagedObjects(array $objects, array $managedObjects)
    {
        foreach ($objects as $object) {
            $uuid = $object->get('uuid');
            if (isset($managedObjects[$uuid])) {
                $object->setManagedObject($managedObjects[$uuid]);
            }
        }
    }

    protected function fetchCurrentProblems(): array
    {
        $db = $this->db->getDbAdapter();
        $all = [];
        $query = $db->select()->from(self::TABLE);
        foreach ($db->fetchAll($query) as $row) {
            $all[$row->uuid][$row->rule_name] = $row;
        }

        return $all;
    }

    /**
     * @param BaseDbObject[] $objects
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function checkObjects(array $objects, $folderType)
    {
        $runner = new CheckRunner($this->db);
        $runner->preloadTreeFor($folderType);

        $db = $this->db->getDbAdapter();
        $results = [];
        foreach ($objects as $object) {
            $results[$object->get('uuid')] = $runner->checkForDb($object);
        }
        $db->beginTransaction();
        $checked = [];
        $current = &$this->currentState;
        foreach ($results as $uuid => $objectResults) {
            foreach ($objectResults as $name => $state) {
                $checked[$uuid][$name] = true;
                if (isset($current[$uuid][$name])) {
                    $formerState = $current[$uuid][$name];
                    if ($formerState === $state) {
                        continue;
                    }
                    $where = $db->quoteInto('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
                        . $db->quoteInto(' AND rule_name = ?', $name);
                    if ($state === CheckPluginState::NAME_OK) {
                        $db->delete(self::TABLE, $where);
                    } else {
                        $db->update(self::TABLE, [
                            'current_state' => $state,
                            'ts_changed_ms' => Util::currentTimestamp(),
                        ], $where);
                    }
                } elseif ($state !== CheckPluginState::NAME_OK) {
                    $db->insert(self::TABLE, [
                        'uuid'           => $uuid,
                        'current_state'  => $state,
                        'rule_name'      => $name,
                        'ts_created_ms'  => Util::currentTimestamp(),
                        'ts_changed_ms' => Util::currentTimestamp(),
                    ]);
                    // emit new problem
                }
            }
        }
        $db->commit();
        $this->checked = $checked;
    }

    protected function dropObsoleteRows()
    {
        $db = $this->db->getDbAdapter();
        $db->beginTransaction();
        $checked = &$this->checked;
        $current = &$this->currentState;
        foreach ($current as $uuid => $names) {
            foreach ($names as $name => $state) {
                if (! isset($checked[$uuid][$name])) {
                    $where = $db->quoteInto('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
                        . $db->quoteInto(' AND rule_name = ?', $name);
                    $db->delete(self::TABLE, $where);
                }
            }
        }
        $db->commit();
    }
}
