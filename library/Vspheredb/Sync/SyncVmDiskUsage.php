<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmDiskUsage;
use Icinga\Module\Vspheredb\PerformanceData\InfluxDbWriter;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncVmDiskUsage
{
    use SyncHelper;

    /**
     * @param \stdClass $device
     * @throws IcingaException
     */
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
        $vCenterUuid = $vCenter->getUuid();
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('VirtualMachine', ['guest.disk']),
            VirtualMachine::getSelectSet()
        );
        Logger::debug(
            'Got %d VirtualMachines with guest.disk',
            count($result)
        );

        $connection = $vCenter->getConnection();
        $usage = VmDiskUsage::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d vm_disk_usage objects from DB',
            count($usage)
        );

        $seen = [];
        foreach ($result as $vm) {
            $uuid = $vCenter->makeBinaryGlobalUuid($vm->id);
            if (! property_exists($vm->{'guest.disk'}, 'GuestDiskInfo')) {
                // Should we preserve them? Flag outdated?
                continue;
            }
            $root = null;
            $var = null;
            foreach ($vm->{'guest.disk'}->GuestDiskInfo as $info) {
                $path = $info->diskPath;

                // Workaround for phantom partitions seen by open-vm-tools
                // run by systemd with PrivateTmp=true
                if ($path === '/') {
                    $root = $info;
                } elseif ($path === '/var') {
                    $var = $info;
                } elseif (is_object($root) && in_array($path, ['/tmp', '/var/tmp'])) {
                    if ($path === '/var/tmp' && is_object($var)) {
                        $base = $var;
                    } else {
                        $base = $root;
                    }

                    /** @var \stdClass $base */
                    if ($info->capacity === $base->capacity
                        && $info->freeSpace === $base->freeSpace
                    ) {
                        continue;
                    }
                }
                // End of workaround

                $idx = "$uuid$path";
                $seen[$idx] = $idx;
                if (array_key_exists($idx, $usage)) {
                    $usage[$idx]->set('capacity', $info->capacity);
                    $usage[$idx]->set('free_space', $info->freeSpace);
                } else {
                    $usage[$idx] = VmDiskUsage::create([
                        'vm_uuid'      => $uuid,
                        'vcenter_uuid' => $vCenterUuid,
                        'disk_path'    => $path,
                        'capacity'     => $info->capacity,
                        'free_space'   => $info->freeSpace,
                    ], $connection);
                }
            }
        }

        // Logger::debug('Ready to prepare for InfluxDB');
        // $writer = new InfluxDbWriter($this->vCenter);
        // $writer->sendVmDiskUsage($usage);
        $this->storeObjects($vCenter->getDb(), $usage, $seen);
    }
}
