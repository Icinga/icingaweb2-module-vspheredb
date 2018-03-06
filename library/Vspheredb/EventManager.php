<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Module\Vspheredb\MappedClass\BaseMigrationEvent;

class EventManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->eventManager;
    }

    /**
     * @return BaseMigrationEvent[]
     */
    public function queryEvents()
    {
        $specSet = [
            '_this'   => $this->obj,
            'filter' => [
                'type' => [
                    'VmBeingMigratedEvent',
                    'VmBeingHotMigratedEvent',
                    'VmEmigratingEvent',
                    'VmFailedMigrateEvent',
                    'VmMigratedEvent',
                    'DrsVmMigratedEvent',
                ],
            ]
        ];

        $result = $this->api->soapCall('QueryEvents', $specSet);
        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
        }
    }

    public function rewindCollector()
    {
        $specSet = [
            '_this'   => $this->collector(),
        ];

        $result = $this->api->soapCall('RewindCollector', $specSet);
    }

    /**
     * @return BaseMigrationEvent[]
     */
    public function collectFromCollector()
    {
        $specSet = [
            '_this'   => $this->collector(),
            'maxCount' => 1000,
        ];

        $result = $this->api->soapCall('ReadNextEvents', $specSet);

        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
        }
    }

    public function createCollectorForEvents()
    {
        $specSet = [
            '_this'   => $this->obj,
            'filter' => [
                'type' => [
                    'VmBeingMigratedEvent',
                    'VmBeingHotMigratedEvent',
                    'VmEmigratingEvent',
                    'VmFailedMigrateEvent',
                    'VmMigratedEvent',
                    'DrsVmMigratedEvent',
                ],
            ]
        ];

        $result = $this->api->soapCall('CreateCollectorForEvents', $specSet);

        if (property_exists($result, 'returnval')) {
            return $result->returnval;
        } else {
            return [];
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
