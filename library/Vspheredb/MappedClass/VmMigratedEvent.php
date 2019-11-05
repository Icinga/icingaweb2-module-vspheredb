<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class VmMigratedEvent extends BaseMigrationEvent
{
    /** @var HostEventArgument */
    protected $sourceHost;

    /** @var DatastoreEventArgument */
    protected $sourceDatastore;

    /** @var DatacenterEventArgument */
    protected $sourceDatacenter;

    protected function getMigrationDetails(VCenter $vCenter)
    {
        $data = [];

        if ($this->host) {
            $data['destination_host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->host->host);
        }
        if ($this->ds) {
            $data['destination_datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->ds->datastore);
        }
        if ($this->sourceHost) {
            $data['host_uuid'] = $vCenter->makeBinaryGlobalUuid($this->sourceHost->host);
        }
        if ($this->sourceDatastore) {
            $data['datastore_uuid'] = $vCenter->makeBinaryGlobalUuid($this->sourceDatastore->datastore);
        }

        return $data;
    }
}
