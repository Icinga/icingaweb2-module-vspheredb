<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;
use RuntimeException;

enum ObjectType: string
{
    case HOST_SYSTEM = 'host';
    case VIRTUAL_MACHINE = 'vm';
    case DATASTORE = 'datastore';

    /**
     * Create an ObjectType from a database object
     *
     * @param BaseDbObject $object
     *
     * @return self
     */
    public static function fromDbObject(BaseDbObject $object): self
    {
        $dbClass = $object::class;

        return match ($dbClass) {
            HostSystem::class     => self::HOST_SYSTEM,
            VirtualMachine::class => self::VIRTUAL_MACHINE,
            Datastore::class      => self::DATASTORE,
            default               => throw new RuntimeException("'$dbClass' is not supported (1)")
        };
    }

    /**
     * Create an ObjectType from a URL param
     */
    public static function fromParam(string $objectTypeParam): self
    {
        return match ($objectTypeParam) {
            'HostSystem'     => self::HOST_SYSTEM,
            'VirtualMachine' => self::VIRTUAL_MACHINE,
            'Datastore'      => self::DATASTORE,
            default          => throw new InvalidArgumentException('Unsupported object type: ' . $objectTypeParam)
        };
    }

    /**
     * Get the label for the object type
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::HOST_SYSTEM     => 'Host System',
            self::VIRTUAL_MACHINE => 'Virtual Machine',
            self::DATASTORE       => 'Datastore'
        };
    }

    /**
     * Get the URL for the object type
     *
     * @return string
     */
    public function url(): string
    {
        return match ($this) {
            self::HOST_SYSTEM     => 'vspheredb/host',
            self::VIRTUAL_MACHINE => 'vspheredb/vm',
            self::DATASTORE       => 'vspheredb/datastore',
        };
    }

    /**
     * Get the class for the object type
     *
     * @return class-string
     */
    public function class(): string
    {
        return match ($this) {
            self::HOST_SYSTEM     => HostSystem::class,
            self::VIRTUAL_MACHINE => VirtualMachine::class,
            self::DATASTORE       => Datastore::class,
        };
    }

    /**
     * Get the table for the object type
     *
     * @return string
     */
    public function table(): string
    {
        return match ($this) {
            self::HOST_SYSTEM     => 'host_system',
            self::VIRTUAL_MACHINE => 'virtual_machine',
            self::DATASTORE       => 'datastore',
        };
    }
}
