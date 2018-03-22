<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmDisk;
use Icinga\Module\Vspheredb\DbObject\VmHardware;
use Icinga\Module\Vspheredb\DbObject\VmNetworkAdapter;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncVmHardware
{
    use SyncHelper;

    protected function assertValidDeviceKey($device)
    {
        if (! is_int($device->key)) {
            throw new IcingaException(
                'Got invalid device key "%s", integer expected',
                $device->key
            );
        }
    }

    public function run()
    {
        $vCenter = $this->vCenter;
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('VirtualMachine', ['config.hardware']),
            VirtualMachine::getSelectSet()
        );

        Logger::debug(
            'Got %d VirtualMachines with config.hardware',
            count($result)
        );

        $connection = $vCenter->getConnection();
        $hardware = VmHardware::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d vm_hardware objects from DB',
            count($hardware)
        );
        $disks = VmDisk::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d vm_disk objects from DB',
            count($disks)
        );
        $nics = VmNetworkAdapter::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d vm_network_adapter objects from DB',
            count($nics)
        );

        $seen = [];
        foreach ($result as $vm) {
            $uuid = $vCenter->makeBinaryGlobalUuid($vm->id);
            foreach ($vm->{'config.hardware'}->device as $device) {
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

        $this->storeObjects($vCenter->getDb(), $hardware, $seen);
        $this->storeObjects($vCenter->getDb(), $disks, $seen);
        $this->storeObjects($vCenter->getDb(), $nics, $seen);
    }
}
