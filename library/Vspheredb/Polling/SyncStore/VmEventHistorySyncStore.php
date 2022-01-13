<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\MappedClass\KnownEvent;
use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use RuntimeException;

class VmEventHistorySyncStore extends SyncStore
{
    use SyncHelper;

    protected $lastEventKey;
    protected $lastEventTimestamp;

    public function store($result, $class, SyncStats $stats)
    {
        if (empty($result)) {
            return;
        }

        self::runAsTransaction($this->db, function () use ($result, $stats) {
            $this->lastEventKey = $this->getLastEventKey();
            $this->lastEventTimestamp = $this->getLastEventTimeStamp();
            $stats->setFromApi(count($result));
            foreach ($result as $key => $event) {
                if (! isset($event->__class)) {
                    $this->logger->error(json_encode($event));
                    return;
                }
                $event = KnownEvent::fromSerialization($event);
                // printf("%s <= %s\n", $event->key, $this->lastEventKey);
                if (! method_exists($event, 'getTimestampMs')) {
                    throw new RuntimeException('This is not a known event: ' . var_export($event, 1));
                }
                if ($this->lastEventKey
                    && $event->getTimestampMs() <= $this->lastEventTimestamp
                    && $event->key <= $this->lastEventKey
                ) {
                    $this->logger->debug(sprintf(
                        '%s <= %s & %s <= %s skipped',
                        $event->getTimestampMs(),
                        $this->lastEventTimestamp,
                        $event->key,
                        $this->lastEventKey
                    ));
                    // $skipped++;
                    continue;
                }

                if ($event instanceof KnownEvent) {
                    $event->store($this->db, $this->vCenter);
                    $stats->incCreated();
                }/*  elseif ($event instanceof SessionEvent) {
                    // not yet
                } else {
                    $dom = simplexml_load_string($vCenter->getApi()->curl()->getLastResponse());
                    $dom->formatOutput = true;
                    echo $dom->saveXML();

                    print_r($event);
                    exit;
                }*/
            }
        });
    }

    /**
     * @return int
     * @throws \Zend_Db_Select_Exception
     * @throws \gipfl\ZfDb\Exception\SelectException
     */
    protected function getLastEventKey()
    {
        return $this->selectLast('event_key');
    }

    /**
     * @return int
     * @throws \Zend_Db_Select_Exception
     * @throws \gipfl\ZfDb\Exception\SelectException
     */
    public function getLastEventTimeStamp()
    {
        return $this->selectLast('ts_event_ms');
    }

    /**
     * @param string $column
     * @return int
     * @throws \Zend_Db_Select_Exception
     * @throws \gipfl\ZfDb\Exception\SelectException
     */
    protected function selectLast($column)
    {
        $db = $this->db;
        $uuid = $this->vCenter->getUuid();

        $union = $db->select()->union([
            'vmeh' => $db->select()->from(
                'vm_event_history',
                [$column => "MAX($column)"]
            )->where('vcenter_uuid = ?', $uuid),
            'ah' => $db->select()->from(
                'alarm_history',
                [$column => "MAX($column)"]
            )->where('vcenter_uuid = ?', $uuid),
        ], Select::SQL_UNION_ALL);

        return (int) $db->fetchOne(
            $db->select()->from(['u' => $union], "MAX($column)")
        );
    }
}
