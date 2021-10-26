<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\VirtualMachineSelectSet;

class VirtualMachine extends BaseDbObject
{
    use CustomValueSupport;

    protected $keyName = 'uuid';

    protected $table = 'virtual_machine';

    protected $defaultProperties = [
        'uuid'              => null,
        'vcenter_uuid'      => null,
        'annotation'        => null,
        'custom_values'     => null,
        'hardware_memorymb' => null,
        'hardware_numcpu'   => null,
        'hardware_numcorespersocket' => null,
        'template'          => null,
        'bios_uuid'         => null,
        'instance_uuid'     => null,
        'version'           => null,
        'connection_state'  => null,
        'online_standby'    => null,
        'paused'            => null,
        'guest_id'          => null,
        'guest_full_name'   => null,
        'guest_state'       => null,
        'guest_host_name'   => null,
        'guest_ip_address'  => null,
        'guest_tools_status' => null,
        'guest_tools_running_status' => null,
        'guest_tools_version'        => null,
        'resource_pool_uuid'         => null,
        'runtime_host_uuid'          => null,
        'runtime_last_boot_time'     => null,
        'runtime_last_suspend_time'  => null,
        'runtime_power_state'        => null,
        'boot_network_protocol'      => null,
        'boot_order'                 => null,
        'cpu_hot_add_enabled'        => null,
        'memory_hot_add_enabled'     => null,
    ];

    protected $objectReferences = [
        'runtime_host_uuid',
        'resource_pool_uuid'
    ];

    protected $booleanProperties = [
        'template',
        'online_standby',
        'paused',
        'cpu_hot_add_enabled',
        'memory_hot_add_enabled',
    ];

    protected $propertyMap = [
        'config.annotation'          => 'annotation',
        // TODO: Delegate to vm_hardware sync?
        'config.hardware.memoryMB'   => 'hardware_memorymb',
        'config.hardware.numCPU'     => 'hardware_numcpu',
        'config.hardware.numCoresPerSocket' => 'hardware_numcorespersocket',
        'config.template'            => 'template',
        'config.uuid'                => 'bios_uuid',
        'config.instanceUuid'        => 'instance_uuid',
        // config.locationId (uuid) ??
        // config.vmxConfigChecksum -> base64 -> bin(20)
        'config.version'             => 'version',
        'resourcePool'               => 'resource_pool_uuid',
        'runtime.host'               => 'runtime_host_uuid',
        'runtime.powerState'         => 'runtime_power_state',
        'runtime.connectionState'    => 'connection_state',
        'runtime.onlineStandby'      => 'online_standby',
        'runtime.paused'             => 'paused',
        'guest.guestState'           => 'guest_state',
        'guest.toolsRunningStatus'   => 'guest_tools_running_status',
        'guest.toolsVersion'         => 'guest_tools_version',
        'summary.guest.toolsStatus'  => 'guest_tools_status',
        'summary.customValue'        => 'customValues',
        'guest.guestId'              => 'guest_id',
        'guest.guestFullName'        => 'guest_full_name',
        'guest.hostName'             => 'guest_host_name',
        'guest.ipAddress'            => 'guest_ip_address',
        'config.bootOptions'         => 'bootOptions',
        'config.cpuHotAddEnabled'    => 'cpu_hot_add_enabled',
        'config.memoryHotAddEnabled' => 'memory_hot_add_enabled',
        // 'runtime.bootTime' => 'runtime_last_boot_time',
        // 'runtime.suspendTime' 'runtime_last_suspend_time',
    ];

    protected $quickStats;

    /**
     * @return VmQuickStats
     * @throws \Icinga\Exception\NotFoundError
     */
    public function quickStats()
    {
        if ($this->quickStats === null) {
            $this->quickStats = VmQuickStats::load($this->get('uuid'), $this->connection);
        }

        return $this->quickStats;
    }

    /**
     * @param Api $api
     * @return array
     * @throws \Icinga\Exception\AuthenticationException
     */
    public static function fetchAllFromApi(Api $api)
    {
        // Temporarily override parent method. This should be replaced by some
        // map defining minimum version requirements for certain properties.
        $propertySet = static::getDefaultPropertySet();
        if (version_compare($api->getAbout()->apiVersion, '6.0', '<')) {
            $propertySet = array_values(array_filter($propertySet, function ($value) {
                return $value !== 'runtime.paused';
            }));
        }

        return $api->propertyCollector()->collectObjectProperties(
            new PropertySet(static::getType(), $propertySet),
            static::getSelectSet()
        );
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPaused($value)
    {
        // powered off?
        if ($value === null) {
            $value = 'n';
        }

        if (is_bool($value)) {
            $value = DbProperty::booleanToDb($value);
        }

        return $this->reallySet('paused', $value);
    }

    /**
     * @param $value
     */
    protected function setBootOptions($value)
    {
        if ($value === null) {
            return;
        }
        if (property_exists($value, 'networkBootProtocol')) {
            $this->set('boot_network_protocol', $value->networkBootProtocol);
        } else {
            $this->set('boot_network_protocol', null);
        }

        // bootOrder might be missing, should then default to disk, net
        if (property_exists($value, 'bootOrder')) {
            $keys = [];
            foreach ($value->bootOrder as $device) {
                // we might get an empty bootOrder
                if (property_exists($device, 'deviceKey')) {
                    $keys[] = $device->deviceKey;
                }
            }

            $this->set('boot_order', implode(',', $keys));
        } else {
            $this->set('boot_order', null);
        }
    }

    /**
     * Explicitly defined, parent class would ship the same
     *
     * @return VirtualMachineSelectSet
     */
    public static function getSelectSet()
    {
        return new VirtualMachineSelectSet();
    }
}
