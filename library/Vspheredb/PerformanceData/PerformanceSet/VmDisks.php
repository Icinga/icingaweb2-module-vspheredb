<?php

namespace Icinga\Module\Vspheredb\PerformanceData\PerformanceSet;

// https://www.vmware.com/support/developer/converter-sdk/conv61_apireference/virtual_disk_counters.html
class VmDisks extends PerformanceSet
{
    protected $counters = [
        // 'busResets', -> not per instance
        // 'commandsAborted',  -> not per instance
        'numberReadAveraged', // rate, average
        'numberWriteAveraged',
        'readLatencyUS', // rate, latest, microseconds
        'writeLatencyUS',
        'totalReadLatency', // absolute, average, milliseconds
        'totalWriteLatency',

        // small is better, less than 64 LBNs are skipped in order to access the requested data
        'smallSeeks',  // absolute, latest
        'mediumSeeks', // Number of seeks during the interval that were between 64 and 8192 LBNs apart
        'largeSeeks',  // Number of seeks during the interval that were greater than 8192 LBNs apart

        'read', // rate, kiloBytesPerSecond, average
        'write',

        // Average number of outstanding read (write) requests:
        'readOIO', // absolute, latest
        'writeOIO',
        // commands, usage
    ];

    // 'maxTotalLatency', // > 20ms BAD -> ist nicht hier

    protected $countersGroup = 'virtualDisk';

    protected $objectType = 'VirtualMachine';

    public function getMeasurementName()
    {
        return 'VirtualDisk';
    }

    // select FROM virtual_machine vm JOIN vm_disk vmd on vmd.vm_uuid = vm.uuid
    // JOIN vm_hardware vmhw ON vmd.hardware_key = vmhw.hardware_key AND vmhw.vm_uuid = vm.uuid
    // JOIN vm_hardware vmhc ON vmhc.vm_uuid= vm.uuid AND vmhc.hardware_key = vmhw.controller_key\G
    public function getRequiredMetrics()
    {
        $db = $this->getDb();
        return $db->fetchPairs(
            $this->prepareBaseQuery()->columns([
                'o.moref',
                "GROUP_CONCAT(CASE WHEN vmhw.label LIKE 'IDE %' THEN 'ide' ELSE 'scsi' END || vmhc.bus_number || ':' || vmhw.unit_number SEPARATOR ',')",
                // vm.guest_host_name, vmd.capacity, vmhw.label,
                //vmhc.bus_number, vmhw.unit_number, vmhc.label
            ])
            ->group('vm.uuid')
        );
    }

    public function fetchObjectTags()
    {
        return (new DiskTagHelper($this->vCenter))->fetchVmTags();
    }
}
