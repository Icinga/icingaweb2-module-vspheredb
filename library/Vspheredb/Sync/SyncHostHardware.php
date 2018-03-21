<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\DbObject\BaseVmHardwareDbObject;
use Icinga\Module\Vspheredb\DbObject\HostPciDevice;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncHostHardware
{
    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    public function run()
    {
        $vCenter = $this->vCenter;
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('HostSystem', ['hardware.pciDevice']),
            HostSystem::getSelectSet()
        );

        Benchmark::measure(sprintf(
            'Got %d HostSystems with hardware.pciDevice',
            count($result)
        ));

        $connection = $vCenter->getConnection();
        $devices = HostPciDevice::loadAllForVCenter($vCenter);
        Benchmark::measure(sprintf(
            'Got %d host_pci_device objects from DB',
            count($devices)
        ));

        $seen = [];
        foreach ($result as $host) {
            $uuid = $vCenter->makeBinaryGlobalUuid($host->id);
            foreach ($host->{'hardware.pciDevice'}->HostPciDevice as $device) {
                $key = $device->id;
                $idx = "$uuid$key";
                $seen[$idx] = $idx;
                if (! array_key_exists($idx, $devices)) {
                    $devices[$idx] = HostPciDevice::create([
                        'host_uuid' => $uuid,
                        'id'        => $key
                    ], $connection);
                }
                $devices[$idx]->setMapped($device, $vCenter);
            }
        }

        $this->storeObjects($vCenter->getDb(), $devices, $seen);
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
}
