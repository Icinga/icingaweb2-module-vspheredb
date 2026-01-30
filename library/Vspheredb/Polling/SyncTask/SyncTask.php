<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Polling\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\Polling\SyncStore\SyncStore;

abstract class SyncTask
{
    public const UNSPECIFIED = 'unspecified';

    /** @var string */
    protected string $label = self::UNSPECIFIED;

    /** @var string */
    protected string $tableName = self::UNSPECIFIED;

    /** @var string */
    protected string $objectClass = self::UNSPECIFIED;

    /** @var string */
    protected string $selectSetClass = self::UNSPECIFIED;

    /** @var string */
    protected string $propertySetClass = self::UNSPECIFIED;

    /** @var string */
    protected string $syncStoreClass = self::UNSPECIFIED;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return class-string<BaseDbObject>
     */
    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    /**
     * @return class-string
     */
    public function getSelectSetClass(): string
    {
        return $this->selectSetClass;
    }

    /**
     * @return class-string<PropertySet>
     */
    public function getPropertySetClass(): string
    {
        return $this->propertySetClass;
    }

    /**
     * @return class-string<SyncStore>
     */
    public function getSyncStoreClass(): string
    {
        return $this->syncStoreClass;
    }

    public function tweakResult($result)
    {
        return $result;
    }
}
