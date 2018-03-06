<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class VmMigratedEvent extends BaseMigrationEvent
{
    protected function getMigrationDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->host->host->_) && strlen($this->host->host->_)) {
            $data['destination_host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->host->host->_);
        }
        if (isset($this->ds->datastore->_) && strlen($this->ds->datastore->_)) {
            $data['destination_datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->ds->datastore->_);
        }
        if (isset($this->sourceHost->host->_) && strlen($this->sourceHost->host->_)) {
            $data['host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->sourceHost->host->_);
        }
        if (isset($this->sourceDatastore->datastore->_) && strlen($this->sourceDatastore->datastore->_)) {
            $data['datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->sourceDatastore->datastore->_);
        }

        return $data;
    }
}
