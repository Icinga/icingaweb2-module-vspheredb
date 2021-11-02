<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class Datastore extends ManagedEntity
{
    /**
     * DatastoreBrowser used to browse this datastore.

     * @var ManagedObjectReference to a HostDatastoreBrowser
     */
    protected $browser;

    /**
     * Capabilities of this datastore
     *
     * @var DatastoreCapability
     */
    protected $capability;

    /**
     * Hosts attached to this datastore
     * Optional, therefore -> default value
     *
     * @var DatastoreHostMount[]
     */
    protected $host = [];

    /**
     * Specific information about the datastore
     *
     * @var DatastoreInfo
     */
    protected $info;

    /**
     * Configuration of storage I/O resource management for the datastore.
     * Currently we only support storage I/O resource management on VMFS volumes of a datastore.
     *
     * This configuration may not be available if the datastore is not accessible from any host,
     * or if the datastore does not have VMFS volume. The configuration can be modified using the
     * method ConfigureDatastoreIORM_Task
     * Since vSphere API 4.1
     *
     * @var ?StorageIORMInfo
     */
    protected $iormConfiguration;

    /**
     * Global properties of the datastore
     *
     * @var DatastoreSummary
     */
    protected $summary;

    /**
     * Virtual machines stored on this datastore
     *
     * @var array ManagedObjectReference[] to a VirtualMachine[]
     */
    protected $vm = [];
}
