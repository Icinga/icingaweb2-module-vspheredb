<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Director\Data\Db\DbObject as DirectorDbObject;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;
use Icinga\Module\Vspheredb\Util;

abstract class BaseDbObject extends DirectorDbObject
{
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

    public function setMapped($properties)
    {
        foreach ($this->propertyMap as $key => $property) {
            if (property_exists($properties, $key)) {
                $value = $properties->$key;
                if ($this->isObjectReference($property)) {
                    if (empty($value)) {
                        $value = null;
                    } else {
                        $value = Util::extractNumericId($value);
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
            $this->object = ManagedObject::load($this->get('id'), $this->connection);
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
     * @param Db $db
     * @param BaseDbObject[] $dbObjects
     * @param BaseDbObject[] $newObjects
     */
    protected static function storeSync(Db $db, & $dbObjects, & $newObjects)
    {
        $type = static::getType();
        $dba = $db->getDbAdapter();
        Benchmark::measure("Ready to store $type");
        $dba->beginTransaction();
        $modified = 0;
        $dummy = static::dummyObject();
        $newIds = [];
        foreach ($newObjects as $object) {
            $id = Util::extractNumericId($object->id);

            $newIds[$id] = $id;
            if (array_key_exists($id, $dbObjects)) {
                $dbObject = $dbObjects[$id];
            } else {
                $dbObjects[$id] = $dbObject = static::create(['id' => $id], $db);
            }
            $dbObject->setMapped($object);
            if ($dbObject->hasBeenModified()) {
                $dbObject->store();
                $modified++;
            }
        }

        $del = [];
        foreach ($dbObjects as $existing) {
            $id = $existing->get('id');
            if (! array_key_exists($id, $newIds)) {
                $del[] = $id;
            }
        }
        if (! empty($del)) {
            $dba->delete(
                $dummy->getTableName(),
                $dba->quoteInto('id IN (?)', $del)
            );
        }
        $dba->commit();
        Benchmark::measure(sprintf(
            "Stored %d modified $type out of %d",
            $modified,
            count($newObjects)
        ));
    }

    public static function syncFromApi(Api $api, Db $db)
    {
        $type = static::getType();
        Benchmark::measure("Loading existing $type from DB");
        $existing = static::loadAll($db, null, 'id');
        Benchmark::measure(sprintf("Got %d existing $type", count($existing)));
        $objects = static::fetchAllFromApi($api);
        Benchmark::measure(sprintf("Got %d $type", count($objects)));
        static::storeSync($db, $existing, $objects);
    }
}
