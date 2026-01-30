<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\VmwareDataType\NumericRange;

class DistributedVirtualPortgroup extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'distributed_virtual_portgroup';

    protected ?array $defaultProperties = [
        'uuid'           => null,
        'vcenter_uuid'   => null,
        'portgroup_type' => null,
        'distributed_virtual_switch_uuid' => null,
        'vlan'         => null,
        'vlan_ranges'  => null,
        'num_ports'    => null,
    ];

    protected array $objectReferences = [
        'distributed_virtual_switch_uuid'
    ];

    protected array $propertyMap = [
        'config.defaultPortConfig' => 'defaultPortConfig',
        'config.numPorts' => 'num_ports',
        'config.type'     => 'portgroup_type',
        'config.distributedVirtualSwitch' => 'distributed_virtual_switch_uuid',
    ];

    /**
     * @param object $config
     *
     * @return void
     */
    protected function setDefaultPortConfig(object $config): void
    {
        if (property_exists($config, 'vlan')) {
            $this->setDefaultVlan($config->vlan->vlanId);
        }
    }

    /**
     * @param mixed $vlan
     *
     * @return void
     */
    protected function setDefaultVlan(mixed $vlan): void
    {
        if (is_array($vlan)) {
            $ranges = [];
            /** @var NumericRange $range */
            foreach ($vlan as $range) {
                $ranges[] = (object) [
                    'end'   => $range->end,
                    'start' => $range->start,
                ];
            }
            $this->set('vlan_ranges', json_encode($ranges));
            $this->set('vlan', null);
        } else {
            // A value of 0 specifies that you do not want the port associated with a VLAN.
            // A value from 1 to 4094 specifies a VLAN ID for the port.
            $this->set('vlan', $vlan);
            $this->set('vlan_ranges', null);
        }
    }
}
