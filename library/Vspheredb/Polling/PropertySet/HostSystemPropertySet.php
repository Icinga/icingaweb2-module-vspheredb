<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class HostSystemPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('HostSystem', [
                // config.fileSystemVolume.mountInfo
                'name',
                'summary.config.product.apiVersion',
                'summary.config.product.fullName',
                'hardware.biosInfo.biosVersion',
                'hardware.cpuInfo.numCpuPackages',
                'hardware.cpuInfo.numCpuCores',
                'hardware.cpuInfo.numCpuThreads',
                'hardware.systemInfo.model',
                'hardware.systemInfo.uuid',
                'hardware.systemInfo.vendor',
                'runtime.powerState',
                // TODO: Introduce HostRuntimeInfo?
                // https://<vcenter>/mob/?moid=ha%2dhost&doPath=runtime
                // runtime.inMaintenanceMode   boolean  false
                // runtime.inQuarantineMode    boolean  Unset
                'runtime.dasHostState',
                'summary.customValue',
                'summary.hardware.cpuMhz',
                'summary.hardware.cpuModel',
                'summary.hardware.numHBAs',
                'summary.hardware.numNics',

                'summary.hardware.memorySize',
                'hardware.biosInfo.releaseDate',
                'summary.hardware.otherIdentifyingInfo',
            ])
        ];
    }
}
