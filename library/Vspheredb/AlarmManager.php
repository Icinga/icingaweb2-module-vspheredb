<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Application\Logger;
use Icinga\Exception\AuthenticationException;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Select as ZfSelect;

class AlarmManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    /** @var VCenter */
    protected $vCenter;

    /**
     * EventManager constructor.
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->alarmManager;
    }

    /**
     * Just for tests, not used at runtime
     *
     * @return array
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function queryAlarms()
    {
        $result = $this->api->soapCall('GetAlarm', $this->createSpecSet());
        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
        }
    }

    protected function createSpecSet()
    {
        return [
            '_this'  => $this->obj,
        ];
    }
}
