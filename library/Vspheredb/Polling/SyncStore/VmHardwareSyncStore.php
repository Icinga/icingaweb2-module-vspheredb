<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\VmDisk;
use Icinga\Module\Vspheredb\DbObject\VmHardware;
use Icinga\Module\Vspheredb\DbObject\VmNetworkAdapter;
use Icinga\Module\Vspheredb\RemoteSync\SyncHelper;
use Icinga\Module\Vspheredb\RemoteSync\SyncStats;
use InvalidArgumentException;

class VmHardwareSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        $vCenter = $this->vCenter;
        $connection = $vCenter->getConnection();
        $hardware = VmHardware::loadAllForVCenter($vCenter);
        $disks = VmDisk::loadAllForVCenter($vCenter);
        $nics = VmNetworkAdapter::loadAllForVCenter($vCenter);

        $seen = [];
        foreach ($result as $object) {
            $object = (object) $object;
            $uuid = $vCenter->makeBinaryGlobalMoRefUuid($object->obj);
            if (! isset($object->{'config.hardware'})) {
                continue;
            }
            foreach ($object->{'config.hardware'}->device as $device) {
                $this->assertValidDeviceKey($device);
                $key = $device->key;
                $idx = "$uuid$key";
                $seen[$idx] = $idx;
                if (! array_key_exists($idx, $hardware)) {
                    $hardware[$idx] = VmHardware::create([
                        'vm_uuid'      => $uuid,
                        'hardware_key' => $key
                    ], $connection);
                }
                $hardware[$idx]->setMapped($device, $vCenter);

                // Hint: here it would be better to somehow get real VMware
                //       object class names instead of trusting heuristics
                if (property_exists($device, 'diskObjectId')) {
                    if (! array_key_exists($idx, $disks)) {
                        $disks[$idx] = VmDisk::create([
                            'vm_uuid'      => $uuid,
                            'hardware_key' => $key
                        ], $connection);
                    }
                    $disks[$idx]->setMapped($device, $vCenter);
                } elseif (property_exists($device, 'macAddress')
                    && property_exists($device, 'addressType')
                ) {
                    if (! array_key_exists($idx, $nics)) {
                        $nics[$idx] = VmNetworkAdapter::create([
                            'vm_uuid'      => $uuid,
                            'hardware_key' => $key
                        ], $connection);
                    }
                    $nics[$idx]->setMapped($device, $vCenter);
                }
            }
        }

        // Hint: this would result in incorrect "(API: xxx)" log entry, therefore we ignore
        //       stats for our disk/nic m:n tables
        // Got 3578 VirtualMachines with config.hardware
        // Got 45004 vm_hardware objects from DB
        // Got 5050 vm_disk objects from DB
        // Got 3670 vm_network_adapter objects from DB
        // 0 created, 0 changed, 0 deleted out of 45004 objects (API: 45004)
        // 0 created, 1 changed, 0 deleted out of 5050 objects (API: 45004)
        // 0 created, 0 changed, 0 deleted out of 3670 objects (API: 45004)

        $this->storeSyncObjects($connection->getDbAdapter(), $hardware, $seen, $stats);
        $ignoreDiskStats = new SyncStats('VM Hardware / Disks');
        $this->storeSyncObjects($connection->getDbAdapter(), $disks, $seen, $ignoreDiskStats);
        $ignoreNicStats = new SyncStats('VM Hardware / NICs');
        $this->storeSyncObjects($connection->getDbAdapter(), $nics, $seen, $ignoreNicStats);
    }

    protected function assertValidDeviceKey($device)
    {
        if (! is_int($device->key)) {
            throw new InvalidArgumentException(
                'Got invalid device key "%s", integer expected',
                $device->key
            );
        }
    }
}
