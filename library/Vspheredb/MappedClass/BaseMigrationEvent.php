<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

abstract class BaseMigrationEvent extends VmEvent
{
    public function getDbData(VCenter $vCenter)
    {
        return parent::getDbData($vCenter) + $this->getMigrationDetails($vCenter);
    }

    protected function getMigrationDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->destHost->host->_) && strlen($this->destHost->host->_)) {
            $data['destination_host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->destHost->host->_);
        }
        if (isset($this->destDatastore->datastore->_) && strlen($this->destDatastore->datastore->_)) {
            $data['destination_datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->destDatastore->datastore->_);
        }

        return $data;
    }
}
