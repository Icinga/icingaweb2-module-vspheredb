<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Exception;
use Icinga\Module\Vsphere\Api;
use SoapVar;

class FullTraversal
{
    public static function fetchAll(Api $api)
    {
        $result = $api->soapCall(
            'RetrieveProperties',
            static::prepareRetrievePropertiesParam($api)
        );

        $vms = array();
        if (! property_exists($result, 'returnval')) {
            throw new Exception('Got invalid (empty?) result');
        }

        $knownRefs = array(
            'parent',
            'runtime.host'
        );

        foreach ($result->returnval as $row) {
            $data = array(
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            );
            foreach ($row->propSet as $prop) {
                if (in_array($prop->name, $knownRefs)) {
                    // [parent] => stdClass Object (
                    //    [_] => group-v123456
                    //    [type] => Folder, HostSystem etc
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

    protected static function prepareRetrievePropertiesParam(Api $api)
    {
        $si = $api->getServiceInstance();
        return array(
            '_this'   => $si->propertyCollector,
            'specSet' => static::prepareFullSpecSet($api)
        );
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

    protected static function prepareFullSpecSet(Api $api)
    {
        return array(
            'propSet' => array(
                array(
                    'type' => 'HostSystem',
                    'all' => 0,
                    'pathSet' => HostSystem::getDefaultPropertySet()
                ),
                array(
                    'type' => 'VirtualMachine',
                    'all' => 0,
                    'pathSet' => VirtualMachine::getDefaultPropertySet()
                ),
            ),
            'objectSet' => array(
                'obj' => $api->getServiceInstance()->rootFolder,
                'skip' => false,
                'selectSet' => array(
                    static::traverseDC1(),
                    static::traverseDC2(),
                    static::traverseFolder(),
                    static::traverseCR1(),
                    static::traverseCR2(),
                    static::traverseRP1(),
                    static::traverseRP2(),
                ),
            )
        );
    }

    protected static function traverseDC1()
    {
        // Another TraversalSpec object has type Datacenter and name TraverseDC1.
        // Set the path property to hostFolder
        $spec = array(
            'name' => 'TraverseDC1',
            'type' => 'Datacenter',
            'path' => 'hostFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseDC2()
    {
        // Another TraversalSpec object has the type Datacenter and name TraverseDC2
        // Set the path property to vmFolder
        $spec = array(
            'name' => 'TraverseDC2',
            'type' => 'Datacenter',
            'path' => 'vmFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseFolder()
    {
        // One TraversalSpec object has the type Folder and name TraverseFolder
        // Set the path property to childEntity
        $spec = array(
            'name' => 'TraverseFolder',
            'type' => 'Folder',
            'path' => 'childEntity',
            'skip' => false,

            // Because a Folder can be followed by another Folder, a Datacenter, or
            // a ComputeResource, TraverseFolder needs five SelectionSpec objects.
            // Their names are TraverseFolder, TraverseDC1, TraverseDC2, TraverseCR1,
            // and TraverseCR2. Each SelectionSpec object invokes the name of a
            // TraversalSpec object defined elsewhere in the PropertyFilter
            static::makeSelectionSet('TraverseFolder'),
            static::makeSelectionSet('TraverseDC1'),
            static::makeSelectionSet('TraverseDC2'),
            static::makeSelectionSet('TraverseCR1'),
            static::makeSelectionSet('TraverseCR2')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseCR1()
    {
        // Another TraversalSpec object has the type ComputeResource and name TraverseCR1
        // Set the path property to resourcePool
        $spec = array(
            'name' => 'TraverseCR1',
            'type' => 'ComputeResource',
            'path' => 'resourcePool',
            'skip' => false,

            // A ComputeResource object can be followed either by a ResourcePool
            // or a HostSystem. There is no need to traverse a HostSystem, but
            // there are two different ResourcePool TraversalSpecs to cover, so
            // TraverseCR1 needs an array of two SelectionSpec objects, named
            // TraverseRP1 and TraverseRP2
            static::makeSelectionSet('TraverseRP1'),
            static::makeSelectionSet('TraverseRP2')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseCR2()
    {
        // Another TraversalSpec object has the type ComputeResource and name TraverseCR2
        // Set the path property to host
        $spec = array(
            'name' => 'TraverseCR2',
            'type' => 'ComputeResource',
            'path' => 'host',
            'skip' => false
            // TraverseCR2 can lead only to a HostSystem object, so there is no
            // need for it to have a selectSet array
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseRP1()
    {
        // Another TraversalSpec object has the type ResourcePool and name TraverseRP1
        // Set the path property to resourcePool
        $spec = array(
            'name' => 'TraverseRP1',
            'type' => 'ResourcePool',
            'path' => 'resourcePool',
            'skip' => false,
            // TraverseRP1 can lead only to another ResourcePool, but there are
            // two paths out of ResourcePool, so it needs an array of two
            // SelectionSpec objects, named TraverseRP1 and TraverseRP2
            static::makeSelectionSet('TraverseRP1'),
            static::makeSelectionSet('TraverseRP2')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseRP2()
    {
        // Another TraversalSpec object has the type ResourcePool and name TraverseRP2
        // Set the path property to vm
        $spec = array(
            'name' => 'TraverseRP2',
            'type' => 'ResourcePool',
            'path' => 'vm',
            'skip' => false,
            // TraverseRP2 can lead only to VirtualMachine objects, so there
            // is no need for it to have a selectSet array
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }
}
