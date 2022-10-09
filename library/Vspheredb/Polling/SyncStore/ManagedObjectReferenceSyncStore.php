<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Ramsey\Uuid\Uuid;

class ManagedObjectReferenceSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        $connection = $this->vCenter->getConnection();
        $vCenter = $this->vCenter;

        $objects = ManagedObject::loadAllForVCenter($vCenter);
        $fetched = [];
        $nameUuids = [];
        $idToParent = [];
        $vCenterUuid = $vCenter->get('uuid');
        $vmUuidsWithNoParent = [];
        foreach ($result as $obj) {
            $obj = (object) $obj; // Not needed after serialization / unserialization
            $moRef = $obj->obj;
            $name = $obj->name;
            if (! isset($obj->overallStatus)) {
                $obj->overallStatus = 'gray';
            }
            /** @var ManagedObjectReference $moRef */
            $uuid = $vCenter->makeBinaryGlobalMoRefUuid($moRef);
            if (isset($fetched[$uuid])) {
                $this->logger->error(sprintf(
                    'Got MoRef UUID twice: %s/%s VS %s (%s)',
                    $moRef->_,
                    $name,
                    $fetched[$uuid],
                    Uuid::fromBytes($uuid)->toString()
                ));
                return;
            }
            $fetched[$uuid] = $name;
            $nameUuids[$moRef->_] = $uuid;
            if (array_key_exists($uuid, $objects)) {
                $object = $objects[$uuid];
                $object->set('moref', $moRef->_);
                $object->set('vcenter_uuid', $vCenterUuid);
                $object->set('object_name', $name);
                $object->set('object_type', $moRef->type);
                $object->set('overall_status', $obj->overallStatus);
            } else {
                $objects[$uuid] = ManagedObject::create([
                    'uuid'           => $uuid,
                    'vcenter_uuid'   => $vCenterUuid,
                    'moref'          => $moRef->_,
                    'object_name'    => $name,
                    'object_type'    => $moRef->type,
                    'overall_status' => $obj->overallStatus,
                ], $connection);
            }
            if (property_exists($obj, 'parent')) {
                $idToParent[$uuid] = $obj->parent->_;
            } elseif ($moRef->type === 'VirtualMachine') {
                $vmUuidsWithNoParent[] = $uuid;
            }
        }

        if (! empty($vmUuidsWithNoParent)) {
            $this->logger->debug(\sprintf(
                'There are %d VMs without parent',
                \count($vmUuidsWithNoParent)
            ));
        }

        foreach ($idToParent as $uuid => $parentName) {
            if (array_key_exists($parentName, $nameUuids)) {
                $objects[$uuid]->setParent(
                    $objects[$nameUuids[$parentName]]
                );
            } else {
                $this->logger->error(sprintf(
                    "Could not find parent $parentName for %s",
                    $fetched[$uuid]
                ));
            }
        }

        self::runAsTransaction($this->db, function () use ($objects, $fetched, $stats) {
            $new = $same = $del = $mod = [];
            foreach ($objects as $uuid => $object) {
                $name = $object->get('object_name');
                if ($object->hasBeenLoadedFromDb()) {
                    if ($object->hasBeenModified()) {
                        $mod[$uuid] = $name;
                    } elseif (array_key_exists($uuid, $fetched)) {
                        $same[$uuid] = $name;
                    } else {
                        $del[$uuid] = $name;
                    }
                } else {
                    $new[$uuid] = $name;
                }
            }

            $stats->incCreated(count($new));
            $stats->incModified(count($mod));
            $stats->incDeleted(count($del));
            $stats->setFromApi(count($fetched));
            $stats->setFromDb(count($objects) - count($new));
            if (! empty($del)) {
                $dba = $this->db;
                $dba->update('object', [
                    'parent_uuid' => null
                ], $dba->quoteInto('parent_uuid IN (?)', array_keys($del)));
                $dba->delete('object', $dba->quoteInto('uuid IN (?)', array_keys($del)));
            }

            foreach ($objects as $object) {
                $object->store();
            }
        });
    }
}
