<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VirtualMachine extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'virtual_machine';

    protected $defaultProperties = [
        'uuid'              => null,
        'vcenter_uuid'      => null,
        'annotation'        => null,
        'hardware_memorymb' => null,
        'hardware_numcpu'   => null,
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
        'resource_pool_uuid'         => null,
        'runtime_host_uuid'          => null,
        'runtime_last_boot_time'     => null,
        'runtime_last_suspend_time'  => null,
        'runtime_power_state'        => null,
        'boot_network_protocol'      => null,
        'boot_order'                 => null,
    ];

    protected $objectReferences = [
        'runtime_host_uuid',
        'resource_pool_uuid'
    ];

    protected $booleanProperties = [
        'template',
        'online_standby',
        'paused',
    ];

    protected $propertyMap = [
        'config.annotation'          => 'annotation',
        // TODO: Delegate to vm_hardware sync?
        'config.hardware.memoryMB'   => 'hardware_memorymb',
        'config.hardware.numCPU'     => 'hardware_numcpu',
        'config.template'            => 'template',
        'config.uuid'                => 'bios_uuid',
        'config.instanceUuid'        => 'instance_uuid',
        // config.locationId	(uuid) ??
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
        'summary.guest.toolsStatus'  => 'guest_tools_status',
        'guest.guestId'              => 'guest_id',
        'guest.guestFullName'        => 'guest_full_name',
        'guest.hostName'             => 'guest_host_name',
        'guest.ipAddress'            => 'guest_ip_address',
        'config.bootOptions'         => 'bootOptions',
        // 'runtime.bootTime' => 'runtime_last_boot_time',
        // 'runtime.suspendTime' 'runtime_last_suspend_time',
    ];

    protected function setBootOptions($value)
    {
        if (property_exists($value, 'networkBootProtocol')) {
            $this->set('boot_network_protocol', $value->networkBootProtocol);
        } else {
            $this->set('boot_network_protocol', null);
        }

        // bootOrder might be missing, should then default to disk, net
        if (property_exists($value, 'bootOrder')) {
            $keys = [];
            foreach ($value->bootOrder as $device) {
                $keys[] = $device->deviceKey;
            }

            $this->set('boot_order', implode(',', $keys));
        } else {
            $this->set('boot_order', null);
        }
    }
}
