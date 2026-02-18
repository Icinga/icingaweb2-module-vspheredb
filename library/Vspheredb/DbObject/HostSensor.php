<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostSensor extends BaseDbObject
{
    protected ?string $table = 'host_sensor';

    protected ?array $defaultProperties = [
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

    protected array $objectReferences = [
        'host_uuid',
    ];

    protected array $propertyMap = [
        'name'           => 'name',
        'healthState'    => 'health_state',
        'currentReading' => 'current_reading',
        'unitModifier'   => 'unit_modifier',
        'baseUnits'      => 'base_units',
        'rateUnits'      => 'rate_units',
        'sensorType'     => 'sensor_type',
    ];

    // TODO: HostNumericSensorInfo has 'id' since v6.5
    protected string|array|null $keyName = ['host_uuid', 'name'];

    /**
     * @param string $value
     *
     * @return static
     */
    public function setName(string $value): static
    {
        // name has the form "description --- state/identifier"
        // TODO: strip the identifier once we changed the key to 'id'
        //       currently there would be duplicates
        // $value = \preg_replace('/\s---\s.+$/', '', $value);
        if ($value === $this->get('name')) {
            return $this;
        }

        return $this->reallySet('name', $value);
    }

    public function setHealth_state($healthState): void // phpcs:ignore
    {
        if (is_object($healthState)) {
            $this->reallySet('health_state', lcfirst($healthState->key));
        } else {
            $this->reallySet('health_state', $healthState);
        }
    }

    public static function loadAllForVCenter(VCenter $vCenter): array
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
