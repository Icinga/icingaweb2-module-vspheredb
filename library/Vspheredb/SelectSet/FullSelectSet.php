<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

class FullSelectSet extends SelectSet
{
    public function toArray()
    {
        return [
            static::traverseDC1(),
            static::traverseDC2(),
            static::traverseDC3(),
            static::traverseDC4(),
            static::traverseFolder(),
            static::traverseStoragePod(),
            static::traverseCR1(),
            static::traverseCR2(),
            static::traverseRP1(),
            static::traverseRP2(),
        ];
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

    protected static function traverseDC3()
    {
        // Another TraversalSpec object has the type Datacenter and name TraverseDC3
        // Set the path property to datastoreFolder
        $spec = array(
            'name' => 'TraverseDC3',
            'type' => 'Datacenter',
            'path' => 'datastoreFolder',
            'skip' => false,
            static::makeSelectionSet('TraverseFolder')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseDC4()
    {
        // Another TraversalSpec object has the type Datacenter and name TraverseDC4
        // Set the path property to networkFolder
        $spec = array(
            'name' => 'TraverseDC4',
            'type' => 'Datacenter',
            'path' => 'networkFolder',
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
            static::makeSelectionSet('TraverseStoragePod'),
            static::makeSelectionSet('TraverseDC1'),
            static::makeSelectionSet('TraverseDC2'),
            static::makeSelectionSet('TraverseDC3'),
            static::makeSelectionSet('TraverseDC4'),
            static::makeSelectionSet('TraverseCR1'),
            static::makeSelectionSet('TraverseCR2')
        );

        return new SoapVar($spec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    protected static function traverseStoragePod()
    {
        $spec = array(
            'name' => 'TraverseStoragePod',
            'type' => 'StoragePod',
            'path' => 'childEntity',
            'skip' => false
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
