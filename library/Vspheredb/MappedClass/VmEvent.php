<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

abstract class VmEvent extends KnownEvent
{
    protected $table = 'vm_event_history';

    /** @var bool  Indicates whether or not the virtual machine is marked as a template */
    public $template;

    public function getDbData(VCenter $vCenter)
    {
        return parent::getDbData($vCenter) + $this->getVmEventDetails($vCenter);
    }

    protected function getDatacenterUuid(VCenter $vCenter)
    {
        return $this->datacenter ? $vCenter->makeBinaryGlobalUuid($this->datacenter->datacenter) : null;
    }

    protected function getDatastoreUuid(VCenter $vCenter)
    {
        return $this->ds ? $vCenter->makeBinaryGlobalUuid($this->ds->datastore) : null;
    }

    protected function getComputeResourceUuid(VCenter $vCenter)
    {
        return $this->computeResource ? $vCenter->makeBinaryGlobalUuid($this->computeResource->computeResource) : null;
    }

    protected function getHostUuid(VCenter $vCenter)
    {
        return $this->host ? $vCenter->makeBinaryGlobalUuid($this->host->host) : null;
    }

    protected function getVmUuid(VCenter $vCenter)
    {
        return $this->vm ? $vCenter->makeBinaryGlobalUuid($this->vm->vm) : null;
    }

    protected function getConfigSpec(VCenter $vCenter)
    {
        return isset($this->configSpec) ? json_encode($this->configSpec) : null;
    }

    protected function getConfigChanges(VCenter $vCenter)
    {
        return isset($this->configChanges) ? json_encode($this->configChanges) : null;
    }

    protected function getVmEventDetails(VCenter $vCenter)
    {
        return [
            'is_template'           => $this->template ? 'y' : 'n',
            'user_name'             => $this->userName,
            'datacenter_uuid'       => $this->getDatacenterUuid($vCenter),
            'datastore_uuid'        => $this->getDatacenterUuid($vCenter),
            'host_uuid'             => $this->getHostUuid($vCenter),
            'vm_uuid'               => $this->getVmUuid($vCenter),
            'compute_resource_uuid' => $this->getComputeResourceUuid($vCenter),
            'config_spec'           => $this->getConfigSpec($vCenter),
            'config_changes'        => $this->getConfigChanges($vCenter)
        ];
    }
}
