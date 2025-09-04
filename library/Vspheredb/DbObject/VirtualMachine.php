<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Util;

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
        'guest_ip_addresses'         => null,
        'guest_ip_stack'             => null,
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
        'runtime.paused'             => 'paused', // 6.0
        'guest.guestState'           => 'guest_state',
        'guest.toolsRunningStatus'   => 'guest_tools_running_status',
        'guest.toolsVersion'         => 'guest_tools_version',
        'summary.guest.toolsStatus'  => 'guest_tools_status',
        'summary.customValue'        => 'customValues',
        'guest.guestId'              => 'guest_id',
        'guest.guestFullName'        => 'guest_full_name',
        'guest.hostName'             => 'guest_host_name',
        'guest.ipAddress'            => 'guest_ip_address',
        'guest.net'                  => 'net',
        'guest.ipStack'              => 'guestIpStack',
        'config.bootOptions'         => 'bootOptions',
        'config.cpuHotAddEnabled'    => 'cpu_hot_add_enabled',
        'config.memoryHotAddEnabled' => 'memory_hot_add_enabled',
        // 'runtime.bootTime' => 'runtime_last_boot_time',
        // 'runtime.suspendTime' 'runtime_last_suspend_time',
    ];

    /** @var ?HostSystem */
    protected $runtimeHost = null;

    public function hasRuntimeHost(): bool
    {
        return $this->get('runtime_host_uuid') !== null;
    }

    /**
     * @return HostSystem
     * @throws \Icinga\Exception\NotFoundError
     */
    public function getRuntimeHost(): HostSystem
    {
        $uuid = $this->get('runtime_host_uuid');
        if ($uuid === null) {
            throw new NotFoundError('This VM has no runtime host');
        }
        if ($this->runtimeHost && $this->runtimeHost->get('uuid') !== $uuid) {
            $this->runtimeHost = null;
        }

        if ($this->runtimeHost === null) {
            $this->runtimeHost = HostSystem::load($uuid, $this->connection);
        }

        return $this->runtimeHost;
    }

    /**
     * Set the current runtime host object
     *
     * Can be used to avoid duplicate loading of the very same host. As of this
     * writing, this does NOT change VM properties.
     *
     * @param HostSystem|null $host
     * @return void
     */
    public function setRuntimeHost(?HostSystem $host)
    {
        if ($host === null) {
            $this->runtimeHost = null;
            return;
        }

        if ($host->get('uuid') !== $this->get('runtime_host_uuid')) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot set runtime host with UUID %s, expected %s',
                Util::niceUuid($host->get('uuid')),
                Util::niceUuid($this->get('runtime_host_uuid'))
            ));
        }

        $this->runtimeHost = $host;
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
     * Example:
     *
     * 'GuestNicInfo' => [{
     *     'connected' => true,
     *     'deviceConfigId' => 4000,
     *     'dnsConfig' => NULL,
     *     'ipAddress' => [
     *          '192.2.0.4',
     *          'fe80::20c:223f:fe72:93ca',
     *     ],
     *     'ipConfig' => {
     *       'dynamicProperty' => NULL,
     *       'dynamicType' => NULL,
     *       'ipAddress' => [{
     *          'dynamicProperty' => NULL,
     *          'dynamicType' => NULL,
     *          'ipAddress' => '192.2.0.4',
     *          'prefixLength' => 24,
     *          'state' => 'preferred',
     *       }, {
     *          'dynamicProperty' => NULL,
     *          'dynamicType' => NULL,
     *          'ipAddress' => 'fe80::20c:223f:fe72:93ca',
     *          'prefixLength' => 64,
     *          'state' => 'unknown',
     *       }]
     *      },
     *     'macAddress' => '00:02:92:3c:29:73',
     *     'netBIOSConfig' => NULL,
     *     'network' => 'Demo LAN',
     *  }]
     */
    public function setNet($value)
    {
        if ($value === null || ! isset($value->GuestNicInfo)) {
            $this->set('guest_ip_addresses', null);
            return;
        }
        $addresses = [];
        foreach ($value->GuestNicInfo as $nic) {
            $key = $nic->deviceConfigId; // matches hardware_key
            if (! isset($addresses[$key])) {
                $addresses[$key] = (object) [
                    'connected' => $nic->connected,
                    'network'   => $nic->network,
                    'addresses' => [],
                ];
            }

            if (isset($nic->ipConfig->ipAddress)) {
                foreach ($nic->ipConfig->ipAddress as $config) {
                    $addresses[$key]->addresses[] = (object) [
                        'address'      => $config->ipAddress,
                        'prefixLength' => $config->prefixLength,
                        // state is not required, and is an IpAddressStatus enumeration:
                        // * deprecated   Indicates that this is a valid but deprecated address that
                        //                should no longer be used as a source address
                        // * duplicate    Indicates the address has been determined to be non-unique
                        //                on the link, this address will not be reachable.
                        // * inaccessible Indicates that the address is not accessible because
                        //                interface is not operational
                        // * invalid      Indicates that this isn't a valid
                        // * preferred    Indicates that this is a valid address
                        // * tentative    Indicates that the uniqueness of the address on the link
                        //                is presently being verified
                        // * unknown      Indicates that the status cannot be determined
                        'state'        => property_exists($config, 'state') ? $config->state : null,
                    ];
                }
            }
        }

        $this->set('guest_ip_addresses', JsonString::encode((object) $addresses));
    }

    public function setGuestIpStack($value)
    {
        if ($value === null) {
            $this->set('guest_ip_stack', null);
        } else {
            if (isset($value->GuestStackInfo)) {
                $value = $value->GuestStackInfo;
                foreach ($value as &$stack) {
                    unset($stack->dynamicProperty);
                    unset($stack->dynamicType);
                    unset($stack->ipRouteConfig->dynamicProperty);
                    unset($stack->ipRouteConfig->dynamicType);
                    if (isset($stack->ipRouteConfig->ipRoute)) {
                        foreach ($stack->ipRouteConfig->ipRoute as $route) {
                            unset($route->dynamicProperty);
                            unset($route->dynamicType);
                            unset($route->gateway->dynamicProperty);
                            unset($route->gateway->dynamicType);
                        }
                    }
                }
                $this->set('guest_ip_stack', JsonString::encode($value));
            } else {
                $this->set('guest_ip_stack', null); // -> {}
            }
        }
    }

    /**
     * Example:
     * [{
     *   "dnsConfig": {
     *     "dhcp": false,
     *     "domainName": "",
     *     "hostName": "whatever.example.com",
     *     "ipAddress": ["192.0.2.1"],
     *     "searchDomain": []
     *   },
     *   "ipRouteConfig": {
     *     "ipRoute":[{
     *       "network": "0.0.0.0",
     *       "prefixLength": 0,
     *       "gateway": {
     *         "ipAddress": "192.0.2.254",
     *         "device": "0"
     *       }
     *     }, {
     *       "network": "192.0.2.0",
     *       "prefixLength": 24,
     *       "gateway": {"device": "0"}
     *     }, {
     *       "network": "fe80::",
     *       "prefixLength": 64,
     *       "gateway": {"device":"0"}
     *     }, {
     *         "network": "fe80::20c:fe2f:19f6:5b34",
     *         "prefixLength": 128,
     *         "gateway": {"device": "0"}
     *     }, {
     *       "network": "ff00::",
     *       "prefixLength": 8,
     *       "gateway": {"device": "0"}
     *     }]
     *   }
     * }]
     */
    public function guestIpStack(): ?array
    {
        $value = $this->get('guest_ip_stack');
        if ($value === null) {
            return null;
        }

        return JsonString::decode($value);
    }

    public function guestIpAddresses(): \stdClass
    {
        $value = $this->get('guest_ip_addresses');
        if ($value === null) {
            return (object) [];
        }

        return JsonString::decode($value);
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
}
