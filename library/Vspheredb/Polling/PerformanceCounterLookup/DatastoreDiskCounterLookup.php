<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

class DatastoreDiskCounterLookup extends DefaultCounterLookup
{
    protected $objectKey = 'datastore_moref';

    protected $tagColumns = [];

    protected function prepareInstancesQuery(UuidInterface $vCenterUuid = null)
    {
        // VmCounterLookup
        // SELECT object.moref, (NULL), object.uuid, object.object_name, virtual_machine.guest_host_name FROM object INNER JOIN virtual_machine ON object.uuid = virtual_machine.uuid WHERE virtual_machine.template = 'n' ORDER BY virtual_machine.runtime_host_uuid, object.moref;

        // VmNetworkCounterLookup
        // SELECT object.moref, GROUP_CONCAT(vm_network_adapter.hardware_key SEPARATOR ',') AS hardware_key, object.uuid, object.object_name, COALESCE(virtual_machine.guest_host_name, '(null)'), vm_network_adapter.hardware_key, vm_hardware.label FROM object INNER JOIN virtual_machine ON object.uuid = virtual_machine.uuid INNER JOIN vm_network_adapter ON vm_network_adapter.vm_uuid = virtual_machine.uuid INNER JOIN vm_hardware ON vm_hardware.vm_uuid = vm_network_adapter.vm_uuid AND vm_hardware.hardware_key = vm_network_adapter.hardware_key GROUP BY virtual_machine.uuid;

        // HostCounterLookup
        // SELECT object.moref, (NULL), object.uuid, host_system.sysinfo_uuid, object.object_name FROM object INNER JOIN host_system ON object.uuid = host_system.uuid ORDER BY object.moref;

        // HostNetworkCounterLookup
        // SELECT object.moref, GROUP_CONCAT(host_physical_nic.device SEPARATOR ','), object.uuid, host_system.sysinfo_uuid, object.object_name, host_physical_nic.device FROM object INNER JOIN host_system ON object.uuid = host_system.uuid INNER JOIN host_physical_nic ON host_physical_nic.host_uuid = host_system.uuid;

        return $this->prepareBaseQuery($vCenterUuid)
            ->columns([
                'o.moref',
            ]);
    }

    protected function prepareBaseQuery(UuidInterface $vCenterUuid = null)
    {
        $query = $this->db->select()->from(['o' => 'object'], [])
            ->join(['ds' => 'datastore'], 'o.uuid = ds.uuid', []);

        if ($vCenterUuid) {
            $query->where('o.vcenter_uuid = ?', $vCenterUuid->getBytes());
        }

        return $query;
    }
}
