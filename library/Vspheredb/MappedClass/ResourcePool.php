<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class ResourcePool extends ManagedEntity
{
    /**
     * The resource configuration of all direct children (VirtualMachine and ResourcePool) of
     * this resource group.
     *
     * @var ResourceConfigSpec[]
     */
    protected $childConfiguration = [];

    /**
     * Configuration of this resource pool
     *
     * @var ResourceConfigSpec
     */
    protected $config;

    /**
     * The ComputeResource to which this set of one or more nested resource pools belong
     *
     * @var ManagedObjectReference to a ComputeResource
     */
    protected $owner;

    /**
     * The set of child resource pools
     *
     * @var ManagedObjectReference[] to a ResourcePool[]
     */
    protected $resourcePool = [];

    /**
     * Runtime information about a resource pool. The ResourcePoolResourceUsage information
     * within ResourcePoolRuntimeInfo can be transiently stale. Use RefreshRuntime method to
     * update the information. In releases after vSphere API 5.0, vSphere Servers might not
     * generate property collector update notifications for this property.
     *
     * To obtain the latest value of the property, you can use PropertyCollector methods
     * RetrievePropertiesEx or WaitForUpdatesEx.
     *
     * If you use the PropertyCollector.WaitForUpdatesEx method, specify an empty string for
     * the version parameter. Any other version value will not produce any property values as
     * no updates are generated.
     *
     * @var ResourcePoolRuntimeInfo
     */
    protected $runtime;


    /**
     * Basic information about a resource pool. In releases after vSphere API 5.0, vSphere
     * Servers might not generate property collector update notifications for this property.
     * To obtain the latest value of the property, you can use PropertyCollector methods
     * RetrievePropertiesEx or WaitForUpdatesEx.
     *
     * If you use the PropertyCollector.WaitForUpdatesEx method, specify an empty string for
     * the version parameter. Any other version value will not produce any property values as
     * no updates are generated.
     *
     * @var ResourcePoolSummary
     */
    protected $summary;

    /**
     * The set of virtual machines associated with this resource pool
     *
     * @var ManagedObjectReference[] to a VirtualMachine[]
     */
    public $vm = [];
}
