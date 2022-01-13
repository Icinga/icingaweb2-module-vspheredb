<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use DateTime;
use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Adapter_Abstract as ZfDbAdapter;

/**
 * KnownEvent
 *
 * We use this as a base class for all vim.event.Event implementations
 * handled by us
 */
abstract class KnownEvent implements JsonSerialization
{
    /** @var int The parent or group ID */
    public $chainId;

    /** @var int The event ID */
    public $key;

    /** @var string NOT a real string, xsd:dateTime. The time the event was created */
    public $createdTime;

    /** @var string The user who caused the event */
    public $userName;

    /** @var string|null A formatted text message describing the event. The message may be localized.*/
    public $fullFormattedMessage;

    /** @var ComputeResourceEventArgument|null */
    public $computeResource;

    /** @var DatacenterEventArgument|null */
    public $datacenter;

    /** @var DatastoreEventArgument */
    public $ds;

    /** @var HostEventArgument|null */
    public $host;

    /** @var VmEventArgument|null */
    public $vm;

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

    public static function fromSerialization($any)
    {
        $class = $any->__class;
        $self = new $class;
        foreach (unserialize($any->properties) as $key => $value) {
            $self->$key = $value;
        }

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        // TODO: serialize without (un)serialize(), as this needs to work across nodes
        return ['__class' => get_class($this), 'properties' => serialize(get_object_vars($this))];
    }
}
