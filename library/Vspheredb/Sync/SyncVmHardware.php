<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Benchmark;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\BaseVmHardwareDbObject;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmDisk;
use Icinga\Module\Vspheredb\DbObject\VmHardware;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncVmHardware
{
    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

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

        Benchmark::measure(sprintf(
            'Got %d VirtualMachines with config.hardware',
            count($result)
        ));

        $connection = $vCenter->getConnection();
        $hardware = VmHardware::loadAllForVCenter($vCenter);
        Benchmark::measure(sprintf(
            'Got %d vm_hardware objects from DB',
            count($hardware)
        ));
        $disks = VmDisk::loadAllForVCenter($vCenter);
        Benchmark::measure(sprintf(
            'Got %d vm_disk objects from DB',
            count($disks)
        ));

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
                }
                if (property_exists($device, 'macAddress')
                    && property_exists($device, 'addressType')
                ) {
                    $this->handleNetworkAdapter($device);
                }
            }
        }

        $this->storeObjects($vCenter->getDb(), $hardware, $seen);
        $this->storeObjects($vCenter->getDb(), $disks, $seen);
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $db
     * @param BaseVmHardwareDbObject[] $objects
     * @param $seen
     */
    protected function storeObjects(\Zend_Db_Adapter_Abstract $db, array $objects, $seen)
    {
        $insert = 0;
        $update = 0;
        $delete = 0;
        $db->beginTransaction();
        foreach ($objects as $key => $object) {
            if (! array_key_exists($key, $seen)) {
                $object->delete();
                $delete++;
            } elseif ($object->hasBeenLoadedFromDb()) {
                if ($object->hasBeenModified()) {
                    $update++;
                    $object->store();
                }
            } else {
                $object->store();
                $insert++;
            }
        }

        $db->commit();
        Benchmark::measure("$insert created, $update changed, $delete deleted");
    }

    protected function handleNetworkAdapter($device)
    {
        $map = [
            'port.portgroupKey' => 'portgroup_uuid', // make binary uuid
            'port.portKey' => 'port_key',
            'macAddress' => 'mac_address', // binary(6)? new xxeuid?
            'addressType' => 'address_type',
        ];
    }
}
