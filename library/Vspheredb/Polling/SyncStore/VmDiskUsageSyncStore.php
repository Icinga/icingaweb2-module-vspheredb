<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\RemoteSync\SyncHelper;
use Icinga\Module\Vspheredb\RemoteSync\SyncStats;

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
        foreach ($result as $object) {
            $object = (object) $object;
            $uuid = $vCenter->makeBinaryGlobalUuid($object->obj);
            if (! property_exists($object->{'guest.disk'}, 'GuestDiskInfo')) {
                // Should we preserve them? Flag outdated?
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

        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $seen, $stats);
    }
}
