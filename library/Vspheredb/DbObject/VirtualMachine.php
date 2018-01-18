<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Util;

class VirtualMachine extends BaseDbObject
{
    protected $table = 'virtual_machine';

    protected $defaultProperties = [
        'id'                => null,
        'annotation'        => null,
        'hardware_memorymb' => null,
        'hardware_numcpu'   => null,
        'template'          => null,
        'bios_uuid'         => null,
        'instance_uuid'     => null,
        'version'           => null,
        'guest_id'          => null,
        'guest_full_name'   => null,
        'guest_state'       => null,
        'guest_host_name'   => null,
        'guest_ip_address'  => null,
        'guest_tools_running_status' => null,
        'resource_pool_id'           => null,
        'runtime_host_id'            => null,
        'runtime_last_boot_time'     => null,
        'runtime_last_suspend_time'  => null,
        'runtime_power_state'        => null,
    ];

    protected $objectReferences = [
        'runtime_host_id',
        'resource_pool_id'
    ];

    protected $booleanProperties = [
        'template'
    ];

    protected $propertyMap = [
        'config.annotation'          => 'annotation',
        'config.hardware.memoryMB'   => 'hardware_memorymb',
        'config.hardware.numCPU'     => 'hardware_numcpu',
        'config.template'            => 'template',
        'config.uuid'                => 'bios_uuid',
        'config.instanceUuid'        => 'instance_uuid',
        'config.version'             => 'version',
        'resourcePool'               => 'resource_pool_id',
        'runtime.host'               => 'runtime_host_id',
        'runtime.powerState'         => 'runtime_power_state',
        'guest.guestState'           => 'guest_state',
        'guest.toolsRunningStatus'   => 'guest_tools_running_status',
        'guest.guestId'              => 'guest_id',
        'guest.guestFullName'        => 'guest_full_name',
        'guest.hostName'             => 'guest_host_name',
        'guest.ipAddress'            => 'guest_ip_address',
        'storage.perDatastoreUsage'  => 'perDatastoreUsage',
        // 'runtime_last_boot_time'    => $runtime->bootTime,
        // 'runtime_last_suspend_time' => $runtime->suspendTime,
    ];

    protected $perDatastoreUsage;

    protected function setPerDatastoreUsage($value)
    {
        $this->perDatastoreUsage = $this->normalizePerDatastoreUsage(
            $value->VirtualMachineUsageOnDatastore
        );
    }

    protected function normalizePerDatastoreUsage($list)
    {
        $usage = [];
        foreach ($list as $entry) {
            $id = Util::extractNumericId($entry->datastore);
            unset($entry->datastore);
            $entry->datastore_id = $id;
            $usage[$id] = (array) $entry;
        }

        return $usage;
    }

    protected function onInsert()
    {
        if ($this->perDatastoreUsage !== null) {
            $id = $this->get('id');
            foreach ($this->perDatastoreUsage as $usage) {
                $usage['vm_id'] = $id;
                $this->db->insert('vm_datastore_usage', $usage);
            }
        }
    }

    protected function onUpdate()
    {
        if ($this->perDatastoreUsage !== null) {
            $id = $this->get('id');
            $db = $this->db;
            $where = $db->quoteInto('vm_id = ?', $id) . ' AND datastore_id = ?';
            foreach ($this->perDatastoreUsage as $usage) {
                $thisWhere = $db->quoteInto($where, $usage['datastore_id']);
                $updated = $db->update(
                    'vm_datastore_usage',
                    $usage,
                    $thisWhere
                );

                if (! $updated) {
                    // Doesn't work, fails when no change in above query. We need a better sync.
                    // echo $db->quoteInto($where, $usage['datastore_id']);
                    // $usage['vm_id'] = $id;
                    // $this->db->insert('vm_datastore_usage', $usage);
                }
            }
        }
    }
}
