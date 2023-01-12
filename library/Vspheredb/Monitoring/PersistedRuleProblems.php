<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Exception;
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
use Throwable;

class PersistedRuleProblems
{
    protected const TABLE = 'monitoring_rule_problem';
    protected const HISTORY_TABLE = 'monitoring_rule_problem_history';

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
        $this->checked = [];
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
        $this->deleteOutdatedHistoryRows();
        $this->checked = null;
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
        try {
            $checked = $this->processCheckedObjects($results);
            $db->commit();
            $this->rememberCheckedObjects($checked);
        } catch (Throwable $e) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                // Nothing to do here
            }

            throw $e;
        }
    }

    protected function rememberCheckedObjects($checked)
    {
        foreach ($checked as $uuid => $names) {
            foreach ($names as $name => $true) {
                $this->checked[$uuid][$name] = $true;
            }
        }
    }

    /**
     * @param array<string,array<string,CheckResultSet>> $results
     * @return array
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function processCheckedObjects(array $results): array
    {
        $db = $this->db->getDbAdapter();
        $checked = [];
        $current = &$this->currentState;
        foreach ($results as $uuid => $objectResults) {
            foreach ($objectResults as $name => $resultSet) {
                $now = Util::currentTimestamp();
                $state = $resultSet->getState()->getName();
                $checked[$uuid][$name] = true;
                if (isset($current[$uuid][$name])) {
                    $formerRow = $current[$uuid][$name];
                    $formerState = $formerRow->current_state;
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
                            'ts_changed_ms' => $now,
                        ], $where);
                    }
                    $db->insert(self::HISTORY_TABLE, [
                        'uuid'           => $uuid,
                        'current_state'  => $state,
                        'former_state'   => $formerState,
                        'rule_name'      => $name,
                        'ts_changed_ms'  => $now,
                        'output'         => $resultSet->getOutput(),
                    ]);
                } elseif ($state !== CheckPluginState::NAME_OK) {
                    $db->insert(self::TABLE, [
                        'uuid'           => $uuid,
                        'current_state'  => $state,
                        'rule_name'      => $name,
                        'ts_created_ms'  => $now,
                        'ts_changed_ms'  => $now,
                    ]);
                    // emit new problem
                    $db->insert(self::HISTORY_TABLE, [
                        'uuid'           => $uuid,
                        'current_state'  => $state,
                        'former_state'   => CheckPluginState::NAME_OK, // null?
                        'rule_name'      => $name,
                        'ts_changed_ms'  => $now,
                        'output'         => $resultSet->getOutput(),
                    ]);
                }
            }
        }

        return $checked;
    }

    protected function dropObsoleteRows()
    {
        $db = $this->db->getDbAdapter();
        $db->beginTransaction();
        $checked = &$this->checked;
        $current = &$this->currentState;
        $now = Util::currentTimestamp();
        try {
            foreach ($current as $uuid => $names) {
                foreach ($names as $name => $row) {
                    if (! isset($checked[$uuid][$name])) {
                        $where = $db->quoteInto('uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
                            . $db->quoteInto(' AND rule_name = ?', $name);
                        $db->delete(self::TABLE, $where);
                        $db->insert(self::HISTORY_TABLE, [
                            'uuid'           => $uuid,
                            'current_state'  => CheckPluginState::NAME_OK,
                            'former_state'   => $row->current_state,
                            'rule_name'      => $name,
                            'ts_changed_ms'  => $now,
                            'output'         => null,
                        ]);
                    }
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                // Nothing to do here
            }

            throw $e;
        }
    }

    protected function deleteOutdatedHistoryRows()
    {
        $db = $this->db->getDbAdapter();
        $expiration = 86400 * 90;
        $db->delete(self::TABLE, $db->quoteInto('ts_changed_ms < ?', (time() - $expiration) * 1000));
    }
}
