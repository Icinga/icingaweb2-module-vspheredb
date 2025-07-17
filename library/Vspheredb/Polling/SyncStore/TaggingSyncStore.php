<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Ramsey\Uuid\Uuid;

class TaggingSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        $result = self::wantBinaryUuids($result);
        $dbObjects = $class::loadAllForVCenter($this->vCenter);
        $vCenterUuid = $this->vCenter->get('uuid');
        $connection = $this->vCenter->getConnection();

        foreach ($result as $idx => $object) {
            if (array_key_exists($idx, $dbObjects)) {
                $dbObject = $dbObjects[$idx];
            } else {
                $dbObjects[$idx] = $dbObject = $class::create([
                    'vcenter_uuid' => $vCenterUuid
                ], $connection);
            }
            $dbObject->setMapped($object, $this->vCenter);
        }

        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $result, $stats);
    }

    protected static function wantBinaryUuids($result): array
    {
        $binary = [];
        $keys = null;
        foreach ((array) $result as $key => $row) {
            if ($keys === null) {
                $keys = [];
                foreach (array_keys((array) $row) as $possibleKey) {
                    if (str_contains($possibleKey, 'uuid')) {
                        $keys[] = $possibleKey;
                    }
                }
            }
            if (empty($keys)) {
                return $result;
            }

            $newKey = '';
            foreach (explode('/', $key) as $uuid) {
                $newKey .= Uuid::fromString($uuid)->getBytes();
            }
            foreach ($keys as $key) {
                $row->$key = Uuid::fromString($row->$key)->getBytes();
            }
            $binary[$newKey] = $row;
        }

        return $binary;
    }
}
