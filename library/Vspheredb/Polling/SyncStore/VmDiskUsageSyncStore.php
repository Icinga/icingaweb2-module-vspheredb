<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class VmDiskUsageSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        $vCenter = $this->vCenter;
        $vCenterUuid = $vCenter->getUuid();

        $connection = $vCenter->getConnection();
        $dbObjects = $class::loadAllForVCenter($vCenter);

        $seen = [];
        $skipUuids = [];
        foreach ($result as $object) {
            $object = (object) $object;
            if ($object->obj instanceof ManagedObjectReference) {
                $uuid = $vCenter->makeBinaryGlobalMoRefUuid($object->obj);
            } else {
                $uuid = $vCenter->makeBinaryGlobalMoRefUuid(ManagedObjectReference::fromSerialization($object->obj));
            }
            if (! property_exists($object->{'guest.disk'}, 'GuestDiskInfo')) {
                $skipUuids[] = $uuid;
                // Preserve former disks. Should we flag them as outdated?
                continue;
            }
            $root = null;
            $var = null;
            // $lastPath = '';
            // $lastCapacity = null;
            // $lastFree = null;
            foreach ($object->{'guest.disk'}->GuestDiskInfo as $info) {
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

                // Skip nested partitions with same capacity and free space
                // Just an idea, not sure about this. e.g. /vsnap/vpoolX has many
                // subpartitions like /vsnap/vpoolX/fs000, not all the same capacity,
                // different free space
                /*
                if (\substr($path, 0, \strlen($lastPath)) === $lastPath
                    && $lastFree === $info->freeSpace
                    && $lastCapacity === $info->capacity
                ) {
                    continue;
                }
                $lastFree = $info->freeSpace;
                $lastCapacity = $info->capacity;
                $lastPath = $path;
                */

                $idx = "$uuid$path";
                $seen[$idx] = $idx;
                if (array_key_exists($idx, $dbObjects)) {
                    $dbObjects[$idx]->set('capacity', $info->capacity);
                    $dbObjects[$idx]->set('free_space', $info->freeSpace);
                } else {
                    $dbObjects[$idx] = $class::create([
                        'vm_uuid'      => $uuid,
                        'vcenter_uuid' => $vCenterUuid,
                        'disk_path'    => $path,
                        'capacity'     => $info->capacity,
                        'free_space'   => $info->freeSpace,
                    ], $connection);
                }
            }
        }
        if (! empty($skipUuids)) {
            $length = strlen($skipUuids[0]); // 16, it's a UUID
            $map = array_combine($skipUuids, $skipUuids);
            foreach ($dbObjects as $idx => $object) {
                if (isset($map[substr($idx, 0, $length)])) {
                    unset($dbObjects[$idx]);
                }
            }
        }

        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $seen, $stats);
    }
}
