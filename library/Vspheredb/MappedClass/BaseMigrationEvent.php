<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use DateTime;
use Icinga\Module\Vspheredb\DbObject\VCenter;

abstract class BaseMigrationEvent
{
    public function getDbData(VCenter $vCenter)
    {
        $classParts = explode('\\', get_class($this));
        // print_r($this);
        $data = [
            'vcenter_uuid'   => $vCenter->getUuid(),
            'ts_event_ms'    => $this->timeStringToUnixMs($this->createdTime),
            'event_type'     => array_pop($classParts),
            'vm_uuid'        => $vCenter->makeBinaryGlobalUuid($this->vm->vm->_),
            'event_key'      => $this->key,
            'event_chain_id' => $this->chainId,
            'is_template'    => $this->template ? 'y' : 'n',
        ];

        if (isset($this->userName) && strlen($this->userName)) {
            $data['user_name'] = $this->userName;
        }

        if (isset($this->dvs->dvs->_) && strlen($this->dvs->dvs->_)) {
            $data['dvs_uuid'] = $vCenter->makeBinaryGlobalUuid($this->dvs->dvs->_);
        }

        if (isset($this->net->network->_) && strlen($this->net->network->_)) {
            $data['network_uuid'] = $vCenter->makeBinaryGlobalUuid($this->net->network->_);
        }

        // Hmmm... there is sourceDatacenter in VmMigratedEvent
        if (isset($this->datacenter->datacenter->_) && strlen($this->datacenter->datacenter->_)) {
            $data['datacenter_uuid'] = $vCenter->makeBinaryGlobalUuid($this->datacenter->datacenter->_);
        }
        if (isset($this->computeResource->computeResource->_) && strlen($this->computeResource->computeResource->_)) {
            $data['compute_resource_uuid'] = $vCenter->makeBinaryGlobalUuid($this->computeResource->computeResource->_);
        }

        if (isset($this->fullFormattedMessage) && strlen($this->fullFormattedMessage)) {
            $data['message'] = $this->fullFormattedMessage;
        }

        return $data + $this->getMigrationDetails($vCenter);
    }

    protected function getMigrationDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->host->host->_) && strlen($this->host->host->_)) {
            $data['host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->host->host->_);
        }
        if (isset($this->ds->datastore->_) && strlen($this->ds->datastore->_)) {
            $data['datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->ds->datastore->_);
        }
        if (isset($this->destHost->host->_) && strlen($this->destHost->host->_)) {
            $data['destination_host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->destHost->host->_);
        }
        if (isset($this->destDatastore->datastore->_) && strlen($this->destDatastore->datastore->_)) {
            $data['destination_datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->destDatastore->datastore->_);
        }

        return $data;
    }

    protected function timeStringToUnixMs($string)
    {
        $time = new DateTime($string);

        return (int) (1000 * $time->format('U.u'));
    }
}
