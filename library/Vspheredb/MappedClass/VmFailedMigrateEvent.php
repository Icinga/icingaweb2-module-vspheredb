<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class VmFailedMigrateEvent extends BaseMigrationEvent
{
    public function getMigrationDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->reason->fault->faultMessage)) {
            $data['fault_message'] = json_encode($this->reason->fault->faultMessage);
        }
        if (isset($this->reason->fault->reason)) {
            $data['fault_reason'] = json_encode($this->reason->fault->reason);
        }

        return parent::getMigrationDetails($vCenter) + $data;
    }
}
