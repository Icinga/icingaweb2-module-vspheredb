<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Adapter_Abstract as ZfDbAdapter;

class VmSuspendedEvent extends VmEvent
{
    /**
     * @param ZfDbAdapter $db
     * @param VCenter $vCenter
     * @throws \Zend_Db_Adapter_Exception
     */
    public function store(ZfDbAdapter $db, VCenter $vCenter)
    {
        parent::store($db, $vCenter);

        $db->update('virtual_machine', [
            'runtime_power_state' => 'suspended'
        ], $db->quoteInto('uuid = ?', $vCenter->makeBinaryGlobalUuid($this->vm->vm->_)));
    }
}
