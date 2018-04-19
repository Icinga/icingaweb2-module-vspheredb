<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

abstract class VmEvent extends KnownEvent
{
    protected $table = 'vm_event_history';

    public function getDbData(VCenter $vCenter)
    {
        return parent::getDbData($vCenter) + $this->getVmEventDetails($vCenter);
    }

    protected function getVmEventDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->template)) {
            $data['is_template'] = $this->template ? 'y' : 'n';
        }
        if (isset($this->userName) && strlen($this->userName)) {
            $data['user_name'] = $this->userName;
        }

        // Hmmm... there is sourceDatacenter in VmMigratedEvent
        if (isset($this->datacenter->datacenter->_) && strlen($this->datacenter->datacenter->_)) {
            $data['datacenter_uuid'] = $vCenter->makeBinaryGlobalUuid($this->datacenter->datacenter->_);
        }

        if (isset($this->computeResource->computeResource->_) && strlen($this->computeResource->computeResource->_)) {
            $data['compute_resource_uuid'] = $vCenter->makeBinaryGlobalUuid($this->computeResource->computeResource->_);
        }

        if (isset($this->host->host->_) && strlen($this->host->host->_)) {
            $data['host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->host->host->_);
        }

        if (isset($this->vm->vm->_) && strlen($this->vm->vm->_)) {
            $data['vm_uuid'] = $vCenter->makeBinaryGlobalUuid($this->vm->vm->_);
        }

        if (isset($this->ds->datastore->_) && strlen($this->ds->datastore->_)) {
            $data['datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->ds->datastore->_);
        }

        if (isset($this->configSpec)) {
            $data['config_spec'] = json_encode($this->configSpec);
        }
        if (isset($this->configChanges)) {
            $data['config_changes'] = json_encode($this->configChanges);
        }

        return $data;
    }
}
