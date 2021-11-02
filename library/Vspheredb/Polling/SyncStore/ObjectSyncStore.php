<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\CustomFieldsManager;
use Icinga\Module\Vspheredb\RemoteSync\SyncHelper;
use Icinga\Module\Vspheredb\RemoteSync\SyncStats;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Psr\Log\LoggerInterface;

class ObjectSyncStore extends SyncStore
{
    use SyncHelper;

    const CUSTOM_VALUE_KEY = 'summary.customValue';

    /** @var ?CustomFieldsManager */
    protected $customFieldsManager;

    public function __construct(
        $db,
        VCenter $vCenter,
        LoggerInterface $logger,
        CustomFieldsManager $customFieldsManager = null
    ) {
        $this->customFieldsManager = $customFieldsManager;
        parent::__construct($db, $vCenter, $logger);
    }

    protected function indexByUuid($result)
    {
        // map by key
        $fromApi = [];
        foreach ($result as $object) {
            $object = (object) $object; // Not required after we moved to serialization
            /** @var ManagedObjectReference $moRef */
            $moRef = $object->obj;
            $uuid = $this->vCenter->makeBinaryGlobalUuid($moRef);
            $object->uuid = $uuid;
            if ($this->customFieldsManager) {
                self::mapResultCustomValues($object, $this->customFieldsManager);
            }
            $fromApi[$uuid] = $object;
        }

        return $fromApi;
    }

    public function store($result, $class, SyncStats $stats)
    {
        $result = $this->indexByUuid($result);
        $dbObjects = $class::loadAllForVCenter($this->vCenter);
        $vCenterUuid = $this->vCenter->get('uuid');
        $connection = $this->vCenter->getConnection();

        foreach ($result as $idx => $object) {
            if (array_key_exists($idx, $dbObjects)) {
                $dbObject = $dbObjects[$idx];
            } else {
                $dbObjects[$idx] = $dbObject = $class::create([
                    'uuid'         => $object->uuid,
                    'vcenter_uuid' => $vCenterUuid
                ], $connection);
            }
            $dbObject->setMapped($object, $this->vCenter);
        }

        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $result, $stats);
    }

    protected static function mapResultCustomValues($object, CustomFieldsManager $manager)
    {
        $key = self::CUSTOM_VALUE_KEY;
        if (isset($object->$key) && ! empty((array) $object->$key)) {
            // We fetched single properties, not full objects. Therefore, the property contains type as key:
            $mapped = $manager->mapValues($object->$key->CustomFieldValue);
            $object->$key = $mapped;
        }
    }
}
