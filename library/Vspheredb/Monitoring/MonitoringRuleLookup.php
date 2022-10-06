<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;

class MonitoringRuleLookup
{
    public static function getUrlForObjectType($objectType): string
    {
        switch ($objectType) {
            case 'VirtualMachine':
                return 'vspheredb/vm';
            case 'HostSystem':
                return 'vspheredb/host';
            case 'Datastore':
                return 'vspheredb/datastore';
        }

        throw new InvalidArgumentException('Unsupported object type: ' . $objectType);
    }

    /**
     * @param $objectType
     * @return string|VirtualMachine|HostSystem|Datastore
     */
    public static function getClassForObjectType($objectType): string
    {
        switch ($objectType) {
            case 'VirtualMachine':
                return VirtualMachine::class;
            case 'HostSystem':
                return HostSystem::class;
            case 'Datastore':
                return Datastore::class;
        }

        throw new InvalidArgumentException('Unsupported object type: ' . $objectType);
    }

    public static function getTableForObjectType($objectType): string
    {
        switch ($objectType) {
            case 'VirtualMachine':
                return 'virtual_machine';
            case 'HostSystem':
                return 'host_system';
            case 'Datastore':
                return 'datastore';
        }

        throw new InvalidArgumentException('Unsupported object type: ' . $objectType);
    }
}
