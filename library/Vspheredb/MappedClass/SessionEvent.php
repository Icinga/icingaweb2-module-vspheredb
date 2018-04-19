<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\VCenter;

abstract class SessionEvent extends KnownEvent
{
    public function getDbData(VCenter $vCenter)
    {
        return parent::getDbData($vCenter) + $this->getSessionDetails($vCenter);
    }

    protected function getSessionDetails(VCenter $vCenter)
    {
        $data = [];
        $properties = [
            // UserLoginSessionEvent
            'ipAddress' => 'ip_address',
            'locale' => 'locale',
            'sessionId' => 'session_id',
            // SessionTerminatedEvent
            // -> sessionId
            'terminatedUsername' => 'user_name',
            // NoAccessUserEvent
            // -> ipAddress
            // BadUsernameSessionEvent
            // -> ipAddress
            // GlobalMessageChangedEvent
            'message' => 'global_message'
            // UserLogoutSessionEvent
            // AlreadyAuthenticatedSessionEvent


            // loginTime -> login_time
            // callCount -> call_count
            // 'userAgent' -> user_agent
            // host_uuid (login von root. gibt auch Datacenter und computeresource, aber wozu...)
            // fullFormattedMessage
        ];



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
