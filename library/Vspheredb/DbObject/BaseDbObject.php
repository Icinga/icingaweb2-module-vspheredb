<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Director\Data\Db\DbObject as DirectorDbObject;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;

abstract class BaseDbObject extends DirectorDbObject
{
    /** @var Db $connection Exists in parent, but IDEs need a berrer hint */
    protected $connection;

    protected $keyName = 'id';

    /** @var ManagedObject */
    private $object;

    protected $propertyMap = [];

    protected $objectReferences = [];

    protected $booleanProperties = [];

    public function isObjectReference($property)
    {
        return in_array($property, $this->objectReferences);
    }

    public function isBooleanProperty($property)
    {
        return in_array($property, $this->booleanProperties);
    }

    protected function makeBooleanValue($value)
    {
        if ($value === true) {
            return 'y';
        } elseif ($value === false) {
            return 'n';
        } elseif ($value === null) {
            return null;
        } else {
            throw new ProgrammingError(
                'Boolean expected, got %s',
                var_export($value, 1)
            );
        }
    }

    public function setMapped($properties, VCenter $vCenter)
    {
        foreach ($this->propertyMap as $key => $property) {
            if (property_exists($properties, $key)) {
                $value = $properties->$key;
                if ($this->isObjectReference($property)) {
                    if (empty($value)) {
                        $value = null;
                    } elseif (is_object($value)) {
                        $value = $vCenter->makeBinaryGlobalUuid($value->_);
                    } else {
                        $value = $vCenter->makeBinaryGlobalUuid($value);
                    }
                } elseif ($this->isBooleanProperty($property)) {
                    $value = $this->makeBooleanValue($value);
                }

                $this->set($property, $value);
            }
        }

        return $this;
    }

    public function object()
    {
        if ($this->object === null) {
            $this->object = ManagedObject::load($this->get('uuid'), $this->connection);
        }

        return $this->object;
    }

    /**
     * @param Api $api
     * @return array
     */
    public static function fetchAllFromApi(Api $api)
    {
        return $api->propertyCollector()->collectObjectProperties(
            new PropertySet(static::getType(), static::getDefaultPropertySet()),
            static::getSelectSet()
        );
    }

    /**
     * @return SelectSet
     */
    public static function getSelectSet()
    {
        $class = '\\Icinga\\Module\\Vspheredb\\SelectSet\\' . static::getType() . 'SelectSet';
        return new $class;
    }

    public static function getType()
    {
        $parts = explode('\\', get_class(static::dummyObject()));
        return end($parts);
    }

    protected static function getDefaultPropertySet()
    {
        return array_keys(static::dummyObject()->propertyMap);
    }

    protected static function dummyObject()
    {
        return static::create();
    }

    /**
     * @param VCenter $vCenter
     * @param BaseDbObject[] $dbObjects
     * @param BaseDbObject[] $newObjects
     */
    protected static function storeSync(VCenter $vCenter, & $dbObjects, & $newObjects)
    {
        $type = static::getType();
        $db = $vCenter->getConnection();
        $dba = $vCenter->getDb();
        Benchmark::measure("Ready to store $type");
        $dba->beginTransaction();
        $modified = 0;
        $dummy = static::dummyObject();
        $newUuids = [];
        foreach ($newObjects as $object) {
            $uuid = $vCenter->makeBinaryGlobalUuid($object->id);

            $newUuids[$uuid] = $uuid;
            if (array_key_exists($uuid, $dbObjects)) {
                $dbObject = $dbObjects[$uuid];
            } else {
                $dbObjects[$uuid] = $dbObject = static::create(['uuid' => $uuid], $db);
                $dbObject->set('vcenter_uuid', $vCenter->get('uuid'));
            }
            $dbObject->setMapped($object, $vCenter);
            if ($dbObject->hasBeenModified()) {
                $dbObject->store();
                $modified++;
            }
        }

        $del = [];
        foreach ($dbObjects as $existing) {
            $uuid = $existing->get('uuid');
            if (! array_key_exists($uuid, $newUuids)) {
                $del[] = $uuid;
            }
        }
        if (! empty($del)) {
            $dba->delete(
                $dummy->getTableName(),
                $dba->quoteInto('uuid IN (?)', $del)
            );
        }
        $dba->commit();
        Benchmark::measure(sprintf(
            "Stored %d modified $type out of %d",
            $modified,
            count($newObjects)
        ));
    }

    public static function onStoreSync(Db $db)
    {
    }

    public static function syncFromApi(VCenter $vCenter)
    {
        $type = static::getType();
        $db = $vCenter->getConnection();
        Benchmark::measure("Loading existing $type from DB");
        $existing = static::loadAll($db, null, 'uuid');
        Benchmark::measure(sprintf("Got %d existing $type", count($existing)));
        $objects = static::fetchAllFromApi($vCenter->getApi());
        Benchmark::measure(sprintf("Got %d $type", count($objects)));
        static::storeSync($vCenter, $existing, $objects);
        static::onStoreSync($db);
    }
}
