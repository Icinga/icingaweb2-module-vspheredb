<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostSensor extends BaseDbObject
{
    protected $table = 'host_sensor';

    protected $defaultProperties = [
        'name'            => null,
        'host_uuid'       => null,
        'health_state'    => null,
        'current_reading' => null,
        'unit_modifier'   => null,
        'base_units'      => null,
        'rate_units'      => null,
        'sensor_type'     => null,
        'vcenter_uuid'    => null,
    ];

    protected $objectReferences = [
        'host_uuid',
    ];

    protected $propertyMap = [
        'name'           => 'name',
        'healthState'    => 'health_state',
        'currentReading' => 'current_reading',
        'unitModifier'   => 'unit_modifier',
        'baseUnits'      => 'base_units',
        'rateUnits'      => 'rate_units',
        'sensorType'     => 'sensor_type',
    ];

    protected $keyName = ['host_uuid', 'name'];

    public function setMapped($properties, VCenter $vCenter)
    {
        $this->set('vcenter_uuid', $vCenter->get('uuid'));

        foreach ($this->propertyMap as $key => $property) {
            if (property_exists($properties, $key)) {
                if ($key === 'healthState') {
                    $this->set($property, $properties->$key->key);
                } else {
                    $this->set($property, $properties->$key);
                }
            } else {
                $this->set($property, null);
            }
        }

        return $this;
    }

    /**
     * @param VCenter $vCenter
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter)
    {
        $dummy = new static();
        $objects = static::loadAll(
            $vCenter->getConnection(),
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $vCenter->get('uuid'))
        );

        $result = [];
        foreach ($objects as $object) {
            $result[$object->get('host_uuid') . $object->get('name')] = $object;
        }

        return $result;
    }
}
