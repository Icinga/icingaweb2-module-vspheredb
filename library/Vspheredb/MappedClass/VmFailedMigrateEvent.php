<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class VmFailedMigrateEvent extends BaseMigrationEvent
{
    public function getMigrationDetails(VCenter $vCenter)
    {
        return parent::getMigrationDetails($vCenter) + [
            'fault_message' => json_encode($this->reason->fault->faultMessage),
            'fault_reason'  => $this->reason->fault->reason,
        ];
    }
}
