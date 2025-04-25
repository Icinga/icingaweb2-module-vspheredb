<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Polling\SyncStore\SyncStore;

abstract class SyncTask
{
    public const UNSPECIFIED = 'unspecified';

    /** @var string */
    protected $label = self::UNSPECIFIED;

    /** @var string */
    protected $tableName = self::UNSPECIFIED;

    /** @var string */
    protected $objectClass = self::UNSPECIFIED;

    /** @var string */
    protected $selectSetClass = self::UNSPECIFIED;

    /** @var string */
    protected $propertySetClass = self::UNSPECIFIED;

    /** @var string */
    protected $syncStoreClass = self::UNSPECIFIED;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return string|BaseDbObject IDE hint, it's a string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @return string
     */
    public function getSelectSetClass()
    {
        return $this->selectSetClass;
    }

    /**
     * @return string
     */
    public function getPropertySetClass()
    {
        return $this->propertySetClass;
    }

    /**
     * @return string|SyncStore IDE hint, it's a string
     */
    public function getSyncStoreClass()
    {
        return $this->syncStoreClass;
    }
}
