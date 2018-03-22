<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\HostPciDevice;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncHostHardware
{
    use SyncHelper;

    public function run()
    {
        $vCenter = $this->vCenter;
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('HostSystem', ['hardware.pciDevice']),
            HostSystem::getSelectSet()
        );

        Logger::debug(
            'Got %d HostSystems with hardware.pciDevice',
            count($result)
        );

        $connection = $vCenter->getConnection();
        $devices = HostPciDevice::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d host_pci_device objects from DB',
            count($devices)
        );

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
}
