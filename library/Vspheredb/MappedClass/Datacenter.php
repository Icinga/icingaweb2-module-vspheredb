<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class Datacenter extends ManagedEntity
{
    /** @var DatacenterConfigInfo Configuration of the datacenter */
    public $configuration;

    /**
     * A collection of references to the datastore objects available in this datacenter.
     *
     * @var ManagedObjectReference[] to a Datastore[]
     */
    public $datastore = [];

    /**
     * A reference to the folder hierarchy that contains the datastores for this datacenter.
     * This folder is guaranteed to exist.
     * Since vSphere API 4.0
     *
     * @var ManagedObjectReference[] to a Folder[]
     */
    public $datastoreFolder = [];

    /**
     * A reference to the folder hierarchy that contains the compute resources, including hosts
     * and clusters, for this datacenter.
     * This folder is guaranteed to exist
     *
     * @var ManagedObjectReference[] to a Folder[]
     */
    public $hostFolder = [];

    /**
     * A collection of references to the network objects available in this datacenter.
     *
     * @var ManagedObjectReference[] to a Network[]
     */
    public $network;

    /**
     * A reference to the folder hierarchy that contains the network entities for this datacenter.
     * The folder can include Network, DistributedVirtualSwitch, and DistributedVirtualPortgroup objects.
     * This folder is guaranteed to exist.
     * Since vSphere API 4.0
     *
     * @var ManagedObjectReference[] to a Folder[]
     */
    public $networkFolder = [];

    /**
     * A reference to the folder hierarchy that contains VirtualMachine virtual machine templates
     * (identified by the template property, and VirtualApp objects for this datacenter.
     * Note that a VirtualApp that is a child of a ResourcePool may also be visible in this folder.
     * VirtualApp objects can be nested, but only the parent VirtualApp can be visible in the folder.
     * This folder is guaranteed to exist.
     *
     * @var ManagedObjectReference[] to a Folder[]
     */
    public $vmFolder = [];
}
