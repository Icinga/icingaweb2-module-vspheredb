<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

use Icinga\Module\Vspheredb\SelectSet\HostSystemSelectSet;

class HostSystem extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',
            'parent',
            'vm',
            'datastore',
            'config.product.apiVersion',
            'config.product.fullName',
            'hardware.biosInfo.biosVersion',
            'hardware.biosInfo.releaseDate',
            'hardware.systemInfo.model',
            'hardware.systemInfo.uuid',
            'hardware.systemInfo.vendor',
            'hardware.cpuInfo.numCpuPackages',
            'hardware.cpuInfo.numCpuCores',
            'hardware.cpuInfo.numCpuThreads',
            'runtime.powerState',
            'summary.hardware.cpuModel',
            'summary.hardware.cpuMhz',
            'summary.hardware.memorySize',
            'summary.hardware.numHBAs',
            'summary.hardware.numNics',
            // 'configStatus',
            // 'overallStatus',
            // 'datastore',
        );
    }

    public static function getType()
    {
        return 'HostSystem';
    }

    public static function objectSet($base)
    {
        return array(
            'obj'   => $base,
            'skip'  => false,
            'selectSet' => (new HostSystemSelectSet)->toArray(),
        );
    }
}
