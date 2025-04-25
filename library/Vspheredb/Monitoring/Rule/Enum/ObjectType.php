<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class ObjectType
{
    // No enum, not yet.
    public const HOST_SYSTEM = 'host';
    public const VIRTUAL_MACHINE = 'vm';
    public const DATASTORE = 'datastore';

    public const TYPES = [
        self::HOST_SYSTEM,
        self::VIRTUAL_MACHINE,
        self::DATASTORE,
    ];

    public const TYPE_CLASSES = [
        self::HOST_SYSTEM => HostSystem::class,
        self::VIRTUAL_MACHINE => VirtualMachine::class,
        self::DATASTORE => Datastore::class,
    ];

    public const DB_CLASS_TYPE = [
        HostSystem::class     => self::HOST_SYSTEM,
        VirtualMachine::class => self::VIRTUAL_MACHINE,
        Datastore::class      => self::DATASTORE,
    ];

    public static function getDbObjectType(BaseDbObject $object): string
    {
        return static::getDbClassType(get_class($object));
    }

    public static function getDbClassType($dbClass): string
    {
        if (isset(self::DB_CLASS_TYPE[$dbClass])) {
            return self::DB_CLASS_TYPE[$dbClass];
        }

        throw new \RuntimeException("'$dbClass' is not supported (1)");
    }
}
