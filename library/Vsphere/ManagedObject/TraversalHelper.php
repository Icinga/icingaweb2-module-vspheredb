<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Exception;
use SoapVar;

abstract class TraversalHelper
{
    protected static function flattenReference($ref)
    {
        $res = array();
        foreach ($ref as $r) {
            $res[] = $r->_;
        }

        return $res;
    }

    protected static function makeSelectionSet($name)
    {
        return new SoapVar(
            array('name' => $name),
            SOAP_ENC_OBJECT,
            null,
            null,
            'selectSet',
            null
        );
    }
    public static function makeNiceResult($result)
    {
        if (! property_exists($result, 'returnval')) {
            throw new Exception('Got invalid (empty?) result');
        }

        $knownRefs = array(
            'parent',
            'runtime.host'
        );

        $nice = array();
        foreach ($result->returnval as $row) {
            $data = array(
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            );
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
                        $val = static::flattenReference($val->ManagedObjectReference);
                    }
                    $data[$prop->name] = $val;
                }
            }
            $nice[$row->obj->_] = (object) $data;
        }

        return $nice;
    }
}
