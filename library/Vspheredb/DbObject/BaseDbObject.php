<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonSerialization;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbObject as VspheredbDbObject;
use Icinga\Module\Vspheredb\MappedClass\ElementDescription;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReturnTypeWillChange;

abstract class BaseDbObject extends VspheredbDbObject implements JsonSerialization
{
    protected string|array|null $keyName = 'id';

    /** @var ?ManagedObject */
    private ?ManagedObject $object = null;

    /** @var array */
    protected array $propertyMap = [];

    /** @var array */
    protected array $objectReferences = [];

    /** @var array */
    protected array $booleanProperties = [];

    /** @var array */
    protected array $dateTimeProperties = [];

    /**
     * @param string $uuid
     * @param Db     $connection
     *
     * @return static
     *
     * @throws NotFoundError
     */
    public static function loadWithUuid(string $uuid, Db $connection): static
    {
        if (strlen($uuid) === 16) {
            $uuid = Uuid::fromBytes($uuid);
        } else {
            $uuid = Uuid::fromString($uuid);
        }

        return static::load($uuid->getBytes(), $connection);
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function isObjectReference(string $property): bool
    {
        return $property === 'parent' || in_array($property, $this->objectReferences);
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function isBooleanProperty(string $property): bool
    {
        return in_array($property, $this->booleanProperties);
    }

    protected function isBinaryColumn(string $column): bool
    {
        if ($this->isObjectReference($column)) {
            return true;
        }

        if ($column === 'uuid' || str_ends_with($column, '_uuid')) {
            return true;
        }

        return parent::isBinaryColumn($column);
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function isDateTimeProperty(string $property): bool
    {
        return in_array($property, $this->dateTimeProperties);
    }

    /**
     * @param object  $properties
     * @param VCenter $vCenter
     *
     * @return $this
     */
    public function setMapped(object $properties, VCenter $vCenter): static
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
                    $value = lcfirst($value->key);
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

    #[ReturnTypeWillChange]
    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        $serialized = [];
        foreach ($this->getProperties() as $key => $value) {
            if ($this->isBooleanProperty($key)) {
                $value = DbProperty::dbToBoolean($value);
            } elseif ($this->isObjectReference($key)) {
                $value = Uuid::fromBytes($value)->toString();
            } elseif ($key === 'uuid' || str_ends_with($key, '_uuid')) { // Hint: SHOULD be keys or references
                if (strlen($value) === 16) {
                    $value = Uuid::fromBytes($value)->toString();
                } elseif ($value !== null) {
                    $value = '0x' . bin2hex($value);
                }
            }
            /* elseif ($this->isDateTimeProperty($key)) {
                // TODO: ISO with ms/ns?
            }*/

            $serialized[$key] = $value;
        }

        return (object) $serialized;
    }

    /**
     * @param mixed $any
     *
     * @return static
     */
    public static function fromSerialization(mixed $any): static
    {
        return static::create((array) $any);
    }

    /**
     * @param mixed   $value
     * @param VCenter $vCenter
     *
     * @return string|null
     */
    protected function createUuidForMoref(mixed $value, VCenter $vCenter): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof ManagedObjectReference) {
            return $vCenter->makeBinaryGlobalMoRefUuid($value);
        }

        return $vCenter->makeBinaryGlobalUuid($value);
    }

    /**
     * @return ManagedObject|null
     *
     * @throws NotFoundError
     */
    public function object(): ?ManagedObject
    {
        return $this->object ??= ManagedObject::load($this->get('uuid'), $this->connection);
    }

    /**
     * @param ManagedObject|null $object
     *
     * @return void
     */
    public function setManagedObject(?ManagedObject $object): void
    {
        if ($object === null) {
            $this->object = null;

            return;
        }

        if ($object->get('uuid') !== $this->get('uuid')) {
            throw new InvalidArgumentException(sprintf(
                'Cannot set ManagedObject UUID %s, expected %s',
                Uuid::fromBytes($object->get('uuid'))->toString(),
                Uuid::fromBytes($this->get('uuid'))->toString()
            ));
        }

        $this->object = $object;
    }

    /**
     * @return false|string
     */
    public static function getType(): false|string
    {
        $parts = explode('\\', get_class(static::create()));

        return end($parts);
    }

    /**
     * @param VCenter $vCenter
     *
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter): array
    {
        $dummy = new static();
        $connection = $vCenter->getConnection();

        return static::loadAll(
            $connection,
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $connection->quoteBinary($vCenter->get('uuid'))),
            $dummy->keyName
        );
    }
}
