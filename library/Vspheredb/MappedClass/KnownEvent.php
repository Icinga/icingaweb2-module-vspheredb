<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use DateTime;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Adapter_Abstract as ZfDbAdapter;

abstract class KnownEvent
{
    public $chainId;

    public $key;

    public $createdTime;

    protected $table;

    protected $timestampMs;

    public function getDbData(VCenter $vCenter)
    {
        $classParts = explode('\\', get_class($this));
        $data = [
            'vcenter_uuid'   => $vCenter->getUuid(),
            'ts_event_ms'    => $this->getTimestampMs(),
            'event_type'     => array_pop($classParts),
            'event_key'      => $this->key,
            'event_chain_id' => $this->chainId,
        ];
        if (isset($this->fullFormattedMessage) && strlen($this->fullFormattedMessage)) {
            $data['full_message'] = $this->fullFormattedMessage;
        }

        return $data;
    }

    public function getTimestampMs()
    {
        if ($this->timestampMs === null) {
            $this->timestampMs = $this->timeStringToUnixMs($this->createdTime);
        }

        return $this->timestampMs;
    }

    /**
     * @param ZfDbAdapter $db
     * @param VCenter $vCenter
     * @throws \Zend_Db_Adapter_Exception
     */
    public function store(ZfDbAdapter $db, VCenter $vCenter)
    {
        if ($this->table !== null) {
            $db->insert($this->table, $this->getDbData($vCenter));
        }
    }

    protected function timeStringToUnixMs($string)
    {
        $time = new DateTime($string);

        return (int) (1000 * $time->format('U.u'));
    }
}
