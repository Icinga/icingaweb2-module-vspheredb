<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Adapter_Abstract as ZfDbAdapter;

abstract class AlarmEvent extends KnownEvent
{
    public $alarm;

    protected $table = 'alarm_history';

    public function getDbData(VCenter $vCenter)
    {
        return parent::getDbData($vCenter) + $this->getAlarmDetails($vCenter);
    }

    /**
     * @param ZfDbAdapter $db
     * @param VCenter $vCenter
     * @throws \Zend_Db_Adapter_Exception
     */
    public function store(ZfDbAdapter $db, VCenter $vCenter)
    {
        parent::store($db, $vCenter);

        // TODO: don't do so if it is old
        if (isset($this->to) && isset($this->entity)) {
            $db->update('object', [
                'overall_status' => $this->to
            ], $db->quoteInto('uuid = ?', $vCenter->makeBinaryGlobalUuid($this->entity->entity->_)));
        }
    }

    protected function getAlarmDetails(VCenter $vCenter)
    {
        $data = [];

        if (isset($this->alarm->name)) {
            $data['alarm_name'] = $this->alarm->name;
        }
        if (isset($this->alarm->alarm->_)) {
            $data['alarm_moref'] = $this->alarm->alarm->_;
        }
        if (isset($this->source->entity->_) && strlen($this->source->entity->_)) {
            $data['source_uuid'] = $vCenter->makeBinaryGlobalUuid($this->source->entity->_);
        }
        if (isset($this->entity->entity->_) && strlen($this->entity->entity->_)) {
            $data['entity_uuid'] = $vCenter->makeBinaryGlobalUuid($this->entity->entity->_);
        }
        if (isset($this->from) && strlen($this->from)) {
            $data['status_from'] = $this->from;
        }
        if (isset($this->to) && strlen($this->to)) {
            $data['status_to'] = $this->to;
        }

        return $data;
    }
}
