<?php

namespace Icinga\Module\Vspheredb\DbObject;

abstract class BaseVmHardwareDbObject extends BaseDbObject
{
    protected $keyName = ['vm_uuid', 'hardware_key'];

    public function setMapped($properties, VCenter $vCenter)
    {
        $properties = (object) $properties;
        $this->set('vcenter_uuid', $vCenter->get('uuid'));

        foreach ($this->propertyMap as $key => $property) {
            $value = MappingHelper::getSpecificValue($properties, $key);
            if ($this->isObjectReference($property)) {
                if (empty($value)) {
                    $value = null;
                } elseif (is_object($value)) {
                    $value = $vCenter->makeBinaryGlobalUuid($value->_);
                } else {
                    $value = $vCenter->makeBinaryGlobalUuid($value);
                }
            } elseif ($this->isBooleanProperty($property)) {
                $value = $this->makeBooleanValue($value);
            }

            $this->set($property, $value);
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
            $key = '';
            foreach ($dummy->keyName as $part) {
                // Usually vm_uuid . hardware_key
                $key .= $object->get($part);
            }
            $result[$key] = $object;
        }

        return $result;
    }
}
