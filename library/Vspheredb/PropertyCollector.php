<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\AuthenticationException;
use Icinga\Exception\IcingaException;
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

        try {
            return $this->makeNiceResult(
                $this->api->soapCall('RetrieveProperties', $specSet)
            );
        } catch (AuthenticationException $e) {
            $this->api->logout();
            $this->api->login();
            return $this->makeNiceResult(
                $this->api->soapCall('RetrieveProperties', $specSet)
            );
        }
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

        $nice = [];
        foreach ($result->returnval as $row) {
            $data = [
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            ];

            if (! property_exists($row, 'propSet')) {
                if (property_exists($row, 'missingSet')) {
                    if ($row->missingSet[0]->fault->fault->privilegeId === 'System.View') {
                        throw new AuthenticationException('System.View is required');
                    }
                }

                // This can happen for disconnected/unknown objects. No data? Fine.
                // TODO: check out whether this happens with an invalid/no session
                $nice[$data['id']] = (object) $data;

                continue;
            }
            foreach ($row->propSet as $prop) {
                $data[$prop->name] = $prop->val;
            }

            $nice[$data['id']] = (object) $data;
        }

        return $nice;
    }
}
