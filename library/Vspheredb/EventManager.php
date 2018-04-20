<?php

namespace Icinga\Module\Vspheredb;

use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\AuthenticationException;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\BaseMigrationEvent;
use Icinga\Module\Vspheredb\MappedClass\KnownEvent;
use Icinga\Module\Vspheredb\MappedClass\SessionEvent;
use Zend_Db_Adapter_Abstract as ZfDbAdapter;
use Zend_Db_Select as ZfSelect;

class EventManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    /** @var \stdClass */
    protected $collector;

    protected $lastEventTimestamp;

    /** @var int */
    protected $lastEventKey;

    /** @var VCenter */
    protected $vCenter;

    /**
     * EventManager constructor.
     * @param Api $api
     * @throws AuthenticationException
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->eventManager;
    }

    /**
     * @param VCenter $vCenter
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    public function persistFor(VCenter $vCenter)
    {
        // TODO: move persistence elsewhere
        $this->vCenter = $vCenter;
        $this->lastEventKey = $this->getLastEventKey();
        $this->lastEventTimestamp = $this->getLastEventTimeStamp();

        return $this;
    }

    /**
     * Just for tests, not used at runtime
     *
     * @return BaseMigrationEvent[]
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function queryEvents()
    {
        $result = $this->api->soapCall('QueryEvents', $this->createSpecSet());
        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
        }
    }

    /**
     * @return \stdClass
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    protected function collector()
    {
        if ($this->collector === null) {
            $this->collector = $this->createEventCollector();
            $this->rewindCollector();
        }

        return $this->collector;
    }

    /**
     * @return int
     * @throws \Zend_Db_Select_Exception
     */
    protected function getLastEventTimeStamp()
    {
        $db = $this->vCenter->getDb();
        $uuid = $this->vCenter->getUuid();

        $union = $db->select()->union([
            'vmeh' => $db->select()->from('vm_event_history', ['ts_event_ms' => 'MAX(ts_event_ms)'])
                ->where('vcenter_uuid = ?', $uuid),
            'ah' => $db->select()->from('alarm_history', ['ts_event_ms' => 'MAX(ts_event_ms)'])
                ->where('vcenter_uuid = ?', $uuid),
        ], ZfSelect::SQL_UNION_ALL);

        return (int) $db->fetchOne(
            $db->select()->from(['u' => $union], 'MAX(ts_event_ms)')
        );
    }

    /**
     * @return int
     * @throws \Zend_Db_Select_Exception
     */
    protected function getLastEventKey()
    {
        $db = $this->vCenter->getDb();
        $uuid = $this->vCenter->getUuid();

        $union = $db->select()->union([
            'vmeh' => $db->select()->from('vm_event_history', ['event_key' => 'MAX(event_key)'])
                ->where('vcenter_uuid = ?', $uuid),
            'ah' => $db->select()->from('alarm_history', ['event_key' => 'MAX(event_key)'])
                ->where('vcenter_uuid = ?', $uuid),
        ], ZfSelect::SQL_UNION_ALL);

        return (int) $db->fetchOne(
            $db->select()->from(['u' => $union], 'MAX(event_key)')
        );
    }

    /**
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function rewindCollector()
    {
        $this->runFailSafeWithCollector(function () {
            $result = $this->api->soapCall('RewindCollector', [
                '_this'   => $this->collector(),
            ]);
        });
    }

    /**
     * @return int
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     * @throws \Zend_Db_Adapter_Exception
     */
    public function streamToDb()
    {
        $events = $this->collectFromCollector();
        if (empty($events)) {
            return 0;
        }

        $db = $this->vCenter->getDb();
        $db->beginTransaction();
        $skipped = 0;
        foreach ($events as $key => $event) {
            if ($event->key < $this->lastEventKey) {
                $skipped++;
                continue;
            }
            $this->lastEventTimestamp = $event->getTimestampMs();
            $this->lastEventKey = $event->key;

            if ($event instanceof SessionEvent) {
                // not yet
            } elseif ($event instanceof KnownEvent) {
                try {
                    $event->store($db, $this->vCenter);
                } catch (\Zend_Db_Statement_Exception $e) {
                    // TODO: Improve error handling. Where to store the details?
                    print_r($event);
                    print_r($event->getDbData($this->vCenter));
                    throw($e);
                } catch (\Zend_Db_Adapter_Exception $e) {
                    // TODO: Improve error handling. Where to store the details?
                    print_r($event);
                    print_r($event->getDbData($this->vCenter));
                    throw($e);
                }
            }/* else {
                $dom = simplexml_load_string($vCenter->getApi()->curl()->getLastResponse());
                $dom->formatOutput = true;
                echo $dom->saveXML();

                print_r($event);
                exit;
            }*/
        }
        $db->commit();

        if ($skipped > 0) {
            Logger::debug('Fetched %d events to skip', $skipped);
        }

        return count($events);
    }

    /**
     * @param $callable
     * @return mixed
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    protected function runFailSafeWithCollector($callable)
    {
        try {
            return $callable();
        } catch (\SoapFault $e) {
            if (current($e->detail)->enc_stype === 'ManagedObjectNotFound') {
                $this->collector = $this->createEventCollector();
            }

            return $callable();
        }
    }

    /**
     * @return BaseMigrationEvent[]
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function collectFromCollector()
    {
        $specSet = [
            '_this'   => $this->collector(),
            'maxCount' => 1000,
        ];

        try {
            $result = $this->api->soapCall('ReadNextEvents', $specSet);
        } catch (\SoapFault $e) {
            echo $this->api->curl()->getLastResponse();
            if (property_exists($e, 'faultactor')) {
                var_dump($e->faultactor);
            } else {
                var_dump($e);
            }
            exit;
        }

        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
        }
    }

    protected function createSpecSet()
    {
        $filters = ['type' => $this->getEventTypes()];

        if ($this->lastEventTimestamp) {
            $filters['time'] = [
                'beginTime' => $this->makeDateTime((int) floor($this->lastEventTimestamp / 1000))
            ];
        }

        return [
            '_this'  => $this->obj,
            'filter' => $filters,
        ];
    }

    protected function getEventTypes()
    {
        return [
            'AlarmAcknowledgedEvent',
            'AlarmClearedEvent',
            'AlarmCreatedEvent',
            'AlarmReconfiguredEvent',
            'AlarmRemovedEvent',
            'AlarmStatusChangedEvent',

            'VmBeingMigratedEvent',
            'VmBeingHotMigratedEvent',
            'VmEmigratingEvent',
            'VmFailedMigrateEvent',
            'VmMigratedEvent',
            'DrsVmMigratedEvent',
//
            'VmBeingCreatedEvent',
            'VmCreatedEvent',
            'VmStartingEvent',
            'VmPoweredOnEvent',
            'VmPoweredOffEvent',
            'VmSuspendedEvent',

            'VmStoppingEvent',

            'VmBeingDeployedEvent',
            'VmReconfiguredEvent',

            'VmBeingClonedEvent',
            'VmBeingClonedNoFolderEvent',
            'VmClonedEvent',
            'VmCloneFailedEvent',
        ];
    }

    protected function createSpecSetForVm($vmRef)
    {
        // Cloned -> sourceVm
        return [
            '_this'   => $this->obj,
            'filter' => [
                'entity' => [
                    'entity' => [
                        '_'    => $vmRef,
                        'type' => 'VirtualMachine',
                    ],
                    'recursion' => 'self'
                ],
            ]
        ];
    }

    /**
     * @return array
     * @throws AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function createEventCollector()
    {
        $specSet = $this->createSpecSet();
        $result = $this->api->soapCall('CreateCollectorForEvents', $specSet);
        if (property_exists($result, 'returnval')) {
            /* { returnval => {
                    _ => "session[52dd54f1-28a1-4b84-6bd4-fc45fd9f3b78]52fc6d14-1c07-ffcd-107c-7132b2d263b0",
                    type => "EventHistoryCollector"
            } } */

            return $result->returnval;
        } else {
            throw new AuthenticationException('Unable to create event collector, please check session');
        }
    }

    protected function makeDateTime($timestamp)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    public function makeEntity($name, $type)
    {
        return ['_' => $name, 'type' => $type];
    }
}
