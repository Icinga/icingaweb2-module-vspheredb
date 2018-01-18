<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostSystem extends BaseDbObject
{
    protected $table = 'host_system';

    protected $defaultProperties = [
        'id'                      => null,
        'product_api_version'     => null,
        'product_full_name'       => null,
        'bios_version'            => null,
        'bios_release_date'       => null,
        'sysinfo_vendor'          => null,
        'sysinfo_model'           => null,
        'sysinfo_uuid'            => null,
        'hardware_cpu_model'      => null,
        'hardware_cpu_mhz'        => null,
        'hardware_cpu_packages'   => null,
        'hardware_cpu_cores'      => null,
        'hardware_cpu_threads'    => null,
        'hardware_memory_size_mb' => null,
        'hardware_num_hba'        => null,
        'hardware_num_nic'        => null,
        'runtime_power_state'     => null,
    ];

    protected $propertyMap = [
        'config.product.apiVersion'       => 'product_api_version',
        'config.product.fullName'         => 'product_full_name',
        'hardware.biosInfo.biosVersion'   => 'bios_version',
        // 'hardware.biosInfo.releaseDate'  => 'bios_release_date',
        'hardware.cpuInfo.numCpuPackages' => 'hardware_cpu_packages',
        'hardware.cpuInfo.numCpuCores'    => 'hardware_cpu_cores',
        'hardware.cpuInfo.numCpuThreads'  => 'hardware_cpu_threads',
        'hardware.systemInfo.model'       => 'sysinfo_model',
        'hardware.systemInfo.uuid'        => 'sysinfo_uuid',
        'hardware.systemInfo.vendor'      => 'sysinfo_vendor',
        'runtime.powerState'              => 'runtime_power_state',
        'summary.hardware.cpuMhz'         => 'hardware_cpu_mhz',
        'summary.hardware.cpuModel'       => 'hardware_cpu_model',
        // 'summary.hardware.memorySize'    => 'hardware_memory_size_mb', // div 1024?
        'summary.hardware.numHBAs'        => 'hardware_num_hba',
        'summary.hardware.numNics'        => 'hardware_num_nic',
    ];

    protected static function getDefaultPropertySet()
    {
        return array_merge(
            parent::getDefaultPropertySet(),
            ['summary.hardware.memorySize']
        );
    }

    public function countVms()
    {
        $db = $this->getDb();
        return $db->fetchOne(
            $db->select()
                ->from('virtual_machine', 'COUNT(*)')
                ->where('runtime_host_id = ?', $this->get('id'))
        );
    }

    public function setMapped($properties)
    {
        $this->set(
            'hardware_memory_size_mb',
            floor($properties->{'summary.hardware.memorySize'} / (1024 * 1024))
        );

        return parent::setMapped($properties);
    }
}
