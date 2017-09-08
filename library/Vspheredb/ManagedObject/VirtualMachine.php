<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

use Icinga\Module\Vspheredb\SelectSet\VirtualMachineSelectSet;

class VirtualMachine extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',
            'parent',
            'resourcePool',
            'guest.hostName',
            'guest.ipAddress',
            'guest.guestId',
            'guest.guestFullName',
            'guest.guestState',
            'guest.toolsRunningStatus',
            'runtime.bootTime',
            'runtime.host',
            'runtime.powerState',
            'config.annotation',
            'config.hardware.numCPU',
            'config.hardware.memoryMB',
            'config.template',
            'config.version',
            'config.uuid',
            // 'configStatus',
            // 'overallStatus',
        );
    }

    public static function getType()
    {
        return 'VirtualMachine';
    }

    public static function objectSet($base)
    {
        return array(
            'obj'   => $base,
            'skip'  => false,
            'selectSet' => (new VirtualMachineSelectSet)->toArray(),
        );
    }
}
