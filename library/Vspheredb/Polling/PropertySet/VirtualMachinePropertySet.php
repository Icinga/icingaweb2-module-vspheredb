<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class VirtualMachinePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('VirtualMachine', [
                'config.annotation',
                // TODO: Delegate to vm_hardware sync?
                'config.hardware.memoryMB',
                'config.hardware.numCPU',
                'config.hardware.numCoresPerSocket',
                'config.template',
                'config.uuid',
                'config.instanceUuid',
                // config.locationId (uuid) ??
                // config.vmxConfigChecksum -> base64 -> bin(20)
                'config.version',
                'resourcePool',
                'runtime.host',
                'runtime.powerState',
                'runtime.connectionState',
                'runtime.onlineStandby',
                'runtime.paused',
                'guest.guestState',
                'guest.toolsRunningStatus',
                'guest.toolsVersion',
                'summary.guest.toolsStatus',
                'summary.customValue',
                'guest.guestId',
                'guest.guestFullName',
                'guest.hostName',
                'guest.ipAddress',
                'guest.net',
                'guest.ipStack', // -> gives dnsConfig (missing in guest.net?) and ipRouteConfig
                'config.bootOptions',
                'config.cpuHotAddEnabled',
                'config.memoryHotAddEnabled',
                // 'runtime.bootTime',
                // 'runtime.suspendTime',
            ])
        ];
    }
}
