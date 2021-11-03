<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\Db\DbObject as VspheredbDbObject;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\MappedClass\ElementDescription;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
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
            return $vCenter->makeBinaryGlobalMoRefUuid($value);
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
