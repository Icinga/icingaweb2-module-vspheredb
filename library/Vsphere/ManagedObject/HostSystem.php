<?php

namespace Icinga\Module\Vsphere\ManagedObject;

class HostSystem extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',
            'parent',
            'configStatus',
            'overallStatus',
            'datastore',
            'vm',
            'hardware.biosInfo',
            'hardware.systemInfo.model',
            'hardware.systemInfo.vendor',
            'runtime.powerState',
            'summary.hardware.cpuModel',
            'summary.hardware.cpuMhz',
            'summary.hardware.memorySize',
        );
    }
}
