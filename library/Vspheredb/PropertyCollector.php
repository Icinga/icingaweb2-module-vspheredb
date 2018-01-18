<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;

class PropertyCollector
{
    /** @var Api */
    protected $api;

    protected $obj;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->propertyCollector;
    }

    public function collectProperties($specSet)
    {
        $specSet = array(
            '_this'   => $this->obj,
            'specSet' => $specSet
        );

        return $this->makeNiceResult(
            $this->api->soapCall('RetrieveProperties', $specSet)
        );
    }

    public function collectPropertiesEx($specSet, $options = null)
    {
        $specSet = array(
            '_this'   => $this->obj,
            'specSet' => $specSet,
            'options' => $options
        );

        return $this->api->soapCall('RetrievePropertiesEx', $specSet);
    }

    public function collectObjectProperties(PropertySet $propSet, SelectSet $selectSet)
    {
        $result = $this->collectProperties(
            $this->api->makePropertyFilterSpec($propSet, $selectSet)
        );

        return $result;
    }

    // Might be obsolete:
    protected function flattenReference($ref)
    {
        $res = [];
        foreach ($ref as $r) {
            $res[] = $r->_;
        }

        return $res;
    }

    // Might be obsolete:
    protected function makeNiceResult($result)
    {
        if (! property_exists($result, 'returnval')) {
            return [];
        }

        $knownRefs = [
            'parent',
            'runtime.host',
        ];

        $nice = [];
        foreach ($result->returnval as $row) {
            $data = [
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            ];
            foreach ($row->propSet as $prop) {
                $val = $prop->val;
                if (in_array($prop->name, $knownRefs)) {
                    // [parent] => stdClass Object (
                    //    [_] => group-v123456
                    //    [type] => Folder, HostSystem etc
                    // )
                    $data[$prop->name] = $val->_;
                } else {
                    if (is_object($val) && property_exists($val, 'ManagedObjectReference')) {
                        $val = $this->flattenReference($val->ManagedObjectReference);
                    }
                    $data[$prop->name] = $val;
                }
            }
            $nice[$row->obj->_] = (object) $data;
        }

        return $nice;
    }
}
