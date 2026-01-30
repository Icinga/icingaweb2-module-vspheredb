<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostPciDevice extends BaseDbObject
{
    protected ?string $table = 'host_pci_device';

    protected ?array $defaultProperties = [
        'id'              => null,
        'host_uuid'       => null,
        'bus'             => null,
        'slot'            => null,
        'device_function' => null,
        'class_id'        => null,
        'device_id'       => null,
        'device_name'     => null,
        'sub_device_id'   => null,
        'vendor_id'       => null,
        'vendor_name'     => null,
        'sub_vendor_id'   => null,
        'parent_bridge'   => null,
        'vcenter_uuid'    => null,
    ];

    protected array $objectReferences = [
        'host_uuid',
    ];

    protected array $propertyMap = [
        'id'           => 'id',
        'bus'          => 'bus',
        'slot'         => 'slot',
        'function'     => 'device_function',
        'classId'      => 'class_id',
        'deviceId'     => 'device_id',
        'deviceName'   => 'device_name',
        'subDeviceId'  => 'sub_device_id',
        'vendorId'     => 'vendor_id',
        'vendorName'   => 'vendor_name',
        'subVendorId'  => 'sub_vendor_id',
        'parentBridge' => 'parent_bridge',
    ];

    protected string|array|null $keyName = ['host_uuid', 'id'];

    public function setMapped(object $properties, VCenter $vCenter): static
    {
        $this->set('vcenter_uuid', $vCenter->get('uuid'));

        foreach ($this->propertyMap as $key => $property) {
            if (property_exists($properties, $key)) {
                if (in_array($key, ['bus', 'slot', 'device_function'])) {
                    if (is_int($properties->$key) || ctype_digit($properties->$key)) {
                        $this->set($property, chr($properties->$key));
                    } else {
                        var_dump(ctype_digit($properties->$key));
                        var_dump(is_int($properties->$key));
                        var_dump($properties->$key);
                        var_dump($properties);

                        exit;
                    }
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
     *
     * @return static[]
     *
     * @throws \Icinga\Exception\IcingaException
     */
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
            $result[$object->get('host_uuid') . $object->get('id')] = $object;
        }

        return $result;
    }
}
