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
        if ($this->datacenter) {
            return $vCenter->makeBinaryGlobalUuid($this->datacenter->datacenter);
        } else {
            return null;
        }
    }

    protected function getDatastoreUuid(VCenter $vCenter)
    {
        if ($this->ds) {
            return $vCenter->makeBinaryGlobalUuid($this->ds->datastore);
        } else {
            return null;
        }
    }

    protected function getComputeResourceUuid(VCenter $vCenter)
    {
        if ($this->computeResource) {
            return $vCenter->makeBinaryGlobalUuid($this->computeResource->computeResource);
        } else {
            return null;
        }
    }

    protected function getHostUuid(VCenter $vCenter)
    {
        if ($this->host) {
            return $vCenter->makeBinaryGlobalUuid($this->host->host);
        } else {
            return null;
        }
    }

    protected function getVmUuid(VCenter $vCenter)
    {
        if ($this->vm) {
            return $vCenter->makeBinaryGlobalUuid($this->vm->vm);
        } else {
            return null;
        }
    }

    protected function getConfigSpec(VCenter $vCenter)
    {
        if (isset($this->configSpec)) {
            return \json_encode($this->configSpec);
        } else {
            return null;
        }
    }

    protected function getConfigChanges(VCenter $vCenter)
    {
        if (isset($this->configChanges)) {
            return \json_encode($this->configChanges);
        } else {
            return null;
        }
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
            'config_changes'        => $this->getConfigChanges($vCenter),
        ];
    }
}
