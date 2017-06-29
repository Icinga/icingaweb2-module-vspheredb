<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Exception;
use Icinga\Module\Vsphere\Api;

abstract class ManagedObject
{
    protected static function prepareFetchDefaultsRequest(Api $api)
    {
        $si = $api->getServiceInstance();
        return array(
            '_this'   => $si->propertyCollector,
            'specSet' => static::defaultSpecSet($api)
        );
    }

    public static function fetchWithDefaults(Api $api)
    {
        $result = $api->soapCall(
            'RetrieveProperties',
            static::prepareFetchDefaultsRequest($api)
        );

        $vms = array();
        if (! property_exists($result, 'returnval')) {
            throw new Exception('Got invalid (empty?) result');
        }

        foreach ($result->returnval as $row) {
            $data = array();
            foreach ($row->propSet as $prop) {
                if ($prop->name === 'parent') {
                    // [parent] => stdClass Object (
                    //    [_] => group-v123456
                    //    [type] => Folder
                    // )
                    $data[$prop->name] = $prop->val->_;
                } else {
                    $data[$prop->name] = $prop->val;
                }
            }
            $vms[$row->obj->_] = (object) $data;
        }

        return $vms;
    }
}
