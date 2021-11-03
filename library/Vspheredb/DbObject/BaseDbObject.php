<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Exception;
use gipfl\Json\JsonSerialization;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db\DbObject as VspheredbDbObject;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\MappedClass\ElementDescription;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

abstract class BaseDbObject extends VspheredbDbObject implements JsonSerialization
{
    /** @var Db $connection Exists in parent, but IDEs need a better hint */
    protected $connection;

    protected $keyName = 'id';

    /** @var ManagedObject */
    private $object;

    protected $propertyMap = [];

    protected $objectReferences = [];

    protected $booleanProperties = [];

    protected $dateTimeProperties = [];

    /**
     * @param array $filter
     * @param Db $connection
     * @return static
     * @throws NotFoundError
     */
    public static function findOneBy($filter, Db $connection)
    {
        $result = static::findBy($filter, $connection);

        if (empty($result)) {
            throw new NotFoundError('No object found for given filter');
        }

        if (count($result) > 1) {
            throw new NotFoundError('More than one object found for given filter');
        }

        $object = new static();
        $object->setConnection($connection)->setDbProperties($result[0]);

        return $object;
    }

    /**
     * @param array $filter
     * @param Db $connection
     * @return array
     */
    private static function findBy($filter, Db $connection)
    {
        $db = $connection->getDbAdapter();
        $table = static::create()->getTableName();
        $select = $db->select()->from($table);

        foreach ($filter as $key => $value) {
            if ($key === 'object_name') {
                $type = static::getType();
                $select->join(
                    'object',
                    $db->quoteInto("object.uuid = $table.uuid AND object.object_type = ?", $type),
                    []
                );
            }
            if ($value === null) {
                $select->where($key);
            } elseif (strpos($key, '?') === false) {
                $select->where("$key = ?", $value);
            } else {
                $select->where($key, $value);
            }
        }

        return $db->fetchAll($select);
    }

    public static function listNonGreenObjects(Db $connection)
    {
        $db = $connection->getDbAdapter();
        $type = static::getType();
        $select = $db->select()
            ->from('object', ['uuid', 'overall_status', 'object_name'])
            ->where('object_type = ?', $type)
            ->where('overall_status != ?', 'green')
            ->order("CASE overall_status WHEN 'gray' THEN 1 WHEN 'yellow' THEN 2 WHEN 'red' THEN 3 END DESC")
            ->order('object_name');

        $result = [];
        foreach ($db->fetchAll($select) as $row) {
            $status = $row->overall_status;
            if (isset($result[$status])) {
                $result[$status][$row->uuid] = $row->object_name;
            } else {
                $result[$status] = [$row->uuid => $row->object_name];
            }
        }

        return $result;
    }

    public function isObjectReference($property)
    {
        return $property === 'parent' || in_array($property, $this->objectReferences);
    }

    public function isBooleanProperty($property)
    {
        return in_array($property, $this->booleanProperties);
    }

    public function isDateTimeProperty($property)
    {
        return in_array($property, $this->dateTimeProperties);
    }

    /**
     * @param $properties
     * @param VCenter $vCenter
     * @return $this
     */
    public function setMapped($properties, VCenter $vCenter)
    {
        if ($this->hasProperty('vcenter_uuid')) {
            $this->set('vcenter_uuid', $vCenter->getUuid());
        }
        foreach ($this->propertyMap as $key => $property) {
            if (property_exists($properties, $key)) {
                $value = $properties->$key;
                if ($this->isObjectReference($property)) {
                    $value = $this->createUuidForMoref($value, $vCenter);
                } elseif ($this->isBooleanProperty($property)) {
                    $value = DbProperty::booleanToDb($value);
                } elseif ($this->isDateTimeProperty($property)) {
                    $value = Util::timeStringToUnixMs($value);
                } elseif ($value instanceof ElementDescription) {
                    // Like HostNumericSensorInfo.healthState
                    // Hint: lcfirst -> issue #179, vSphere 7 ships 'Green' instead of 'green',
                    //       at least on that specific system
                    $value = \lcfirst($value->key);
                }
                if ($property === 'customValues') {
                    if (empty((array) $value)) {
                        $value = null;
                    }
                }

                $this->set($property, $value);
            } else {
                $this->set($property, null);
            }
        }

        return $this;
    }

    public function jsonSerialize()
    {
        $serialized = [];
        foreach ($this->getProperties() as $key => $value) {
            if ($this->isBooleanProperty($key)) {
                $value = DbProperty::dbToBoolean($value);
            } elseif ($this->isObjectReference($key)) {
                $value = '0x' . bin2hex($value);
            } elseif ($key === 'uuid' || substr($key, -5) === '_uuid') { // Hint: SHOULD be keys or references
                if (strlen($value) === 16) {
                    $value = Uuid::fromBytes($value)->toString();
                } else {
                    $value = '0x' . bin2hex($value);
                }
            }
            /* elseif ($this->isDateTimeProperty($key)) {
                // TODO: ISO with ms/ns?
            }*/

            $serialized[$key] = $value;
        }

        return $serialized;
    }

    public static function fromSerialization($any)
    {
        return static::create((array) $any);
    }

    protected function createUuidForMoref($value, VCenter $vCenter)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof ManagedObjectReference) {
            return $vCenter->makeBinaryGlobalUuid($value->_);
        } else {
            return $vCenter->makeBinaryGlobalUuid($value);
        }
    }

    /**
     * @return ManagedObject
     * @throws \Icinga\Exception\NotFoundError
     */
    public function object()
    {
        if ($this->object === null) {
            $this->object = ManagedObject::load($this->get('uuid'), $this->connection);
        }

        return $this->object;
    }

    public static function getType()
    {
        $parts = explode('\\', get_class(static::create()));
        return end($parts);
    }

    /**
     * @param VCenter $vCenter
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter)
    {
        $dummy = new static();

        return static::loadAll(
            $vCenter->getConnection(),
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $vCenter->get('uuid')),
            $dummy->keyName
        );
    }
}
