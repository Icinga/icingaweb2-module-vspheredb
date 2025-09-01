<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Db;
use Throwable;

class VcenterCommand extends Command
{
    /**
     * Deprecated
     */
    public function initializeAction()
    {
        $this->fail("Command has been deprecated, please check our documentation");
    }

    public function cleanupAction()
    {
        $tables = [
            "vspheredb_daemonlog",
            "vcenter_sync",
            "compute_resource",
            "host_system",
            "host_pci_device",
            "host_sensor",
            "host_physical_nic",
            "host_virtual_nic",
            "host_hba",
            "virtual_machine",
            "storage_pod",
            "distributed_virtual_switch",
            "distributed_virtual_portgroup",
            "datastore",
            "vm_snapshot",
            "vm_datastore_usage",
            "vm_hardware",
            "vm_disk",
            "vm_disk_usage",
            "vm_network_adapter",
            "host_quick_stats",
            "vm_quick_stats",
            "alarm_history",
            "vm_event_history",
            "counter_300x5",
            "performance_counter",
            "performance_unit",
            "performance_group",
            "performance_collection_interval",
            "perfdata_subscription",
            "tagging_category",
            "tagging_tag",
            "tagging_object_tag"
        ];

        $db = Db::newConfiguredInstance();
        $adapter = $db->getDbAdapter();

        $vcenterIds = $adapter->fetchCol(
            $adapter->select()->distinct()->from("vcenter_server", ["vcenter_id"])->where("vcenter_id IS NOT NULL")
        );
        $q = $adapter->select()->from("vcenter", ["instance_uuid"]);
        if (! empty($vcenterIds)) {
            $q->where("id NOT IN (?)", $vcenterIds);
        }
        $orphanedInstanceUuids = $adapter->fetchCol($q);
        if (empty($orphanedInstanceUuids)) {
            echo "No orphaned vcenter uuids were found\n";
            exit(0);
        }
        echo sprintf(
            "Found %d orphaned vcenter uuids: %s\n",
            count($orphanedInstanceUuids),
            implode(", ", array_map("bin2hex", $orphanedInstanceUuids))
        );

        $adapter->beginTransaction();
        try {
            foreach ($tables as $table) {
                $deleted = $adapter->delete(
                    $table,
                    $adapter->quoteInto("vcenter_uuid IN (?)", $db->quoteBinary($orphanedInstanceUuids))
                );

                echo sprintf("Removed %d orphaned rows from table $table\n", $deleted);
            }

            $adapter->query("SET SESSION foreign_key_checks=0");
            $deleted = $adapter->delete(
                "object",
                $adapter->quoteInto("vcenter_uuid IN (?)", $db->quoteBinary($orphanedInstanceUuids))
            );
            echo sprintf("Removed %d orphaned rows from table object\n", $deleted);
            $adapter->query("SET SESSION foreign_key_checks=1");

            $deleted = $adapter->delete(
                "vcenter",
                $adapter->quoteInto("instance_uuid IN (?)", $db->quoteBinary($orphanedInstanceUuids))
            );
            echo sprintf("Removed %d orphaned rows from table vcenter\n", $deleted);

            $adapter->commit();
        } catch (Throwable $e) {
            $adapter->rollBack();

            $this->fail($e->getMessage());
        }

        // Note that monitoring_rule_set might still contain data.
    }
}
