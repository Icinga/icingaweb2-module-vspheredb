<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use Evenement\EventEmitterTrait;
use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\StoragePod;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\EventManager;
use Icinga\Module\Vspheredb\Sync\SyncHostHardware;
use Icinga\Module\Vspheredb\Sync\SyncHostSensors;
use Icinga\Module\Vspheredb\Sync\SyncManagedObjectReferences;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounterInfo;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounters;
use Icinga\Module\Vspheredb\Sync\SyncQuickStats;
use Icinga\Module\Vspheredb\Sync\SyncVmDatastoreUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmDiskUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmHardware;
use Icinga\Module\Vspheredb\Sync\SyncVmSnapshots;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class SyncRunner
{
    use EventEmitterTrait;

    /** @var VCenter */
    protected $vCenter;

    /** @var EventManager */
    protected $eventManager;

    /** @var LoopInterface */
    protected $loop;

    protected $availableTasks = [];

    protected $immediateTasks = [];

    /** @var Deferred */
    protected $deferred;

    protected $taskNames = [
        'moRefs'           => 'Managed Object References',
        'quickStats'       => 'Quick Stats',
        'hostSystems'      => 'Host Systems',
        'virtualMachines'  => 'Virtual Machines',
        'storagePods'      => 'Storage Pods',
        'dataStores'       => 'Data Stores',
        'hostHardware'     => 'Host Hardware',
        'hostSensors'      => 'Host Sensors',
        'vmHardware'       => 'VM Hardware',
        'vmDiskUsage'      => 'VM Disk Usage',
        'vmDatastoreUsage' => 'VM DataStore Usage',
        'vmSnapshots'      => 'VM Snapshots',
        'eventStream'      => 'Event Stream',
        'perfCounters'     => 'Performance Counters',
        'perfCounterInfo'  => 'Performance Counter Information',
    ];

    /**
     * SyncRunner constructor.
     * @param VCenter $vCenter
     */
    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
        $this->availableTasks = [
            'moRefs' => function () {
                (new SyncManagedObjectReferences($this->vCenter))->sync();
            },
            'quickStats' => function () {
                (new SyncQuickStats($this->vCenter))->run();
            },
            'hostSystems' => function () {
                HostSystem::syncFromApi($this->vCenter);
            },
            'virtualMachines' => function () {
                VirtualMachine::syncFromApi($this->vCenter);
            },
            'storagePods' => function () {
                StoragePod::syncFromApi($this->vCenter);
            },
            'dataStores' => function () {
                Datastore::syncFromApi($this->vCenter);
            },
            'hostHardware' => function () {
                (new SyncHostHardware($this->vCenter))->run();
            },
            'hostSensors' => function () {
                (new SyncHostSensors($this->vCenter))->run();
            },
            'vmHardware' => function () {
                (new SyncVmHardware($this->vCenter))->run();
            },
            'vmDiskUsage' => function () {
                (new SyncVmDiskUsage($this->vCenter))->run();
            },
            'vmDatastoreUsage' => function () {
                (new SyncVmDatastoreUsage($this->vCenter))->run();
            },
            'vmSnapshots' => function () {
                (new SyncVmSnapshots($this->vCenter))->run();
            },
            'eventStream' => function () {
                $this->streamEvents();
            },
            'perfCounters' => function () {
                (new SyncPerfCounters($this->vCenter))->run();
            },
            'perfCounterInfo' => function () {
                (new SyncPerfCounterInfo($this->vCenter))->run();
            },
        ];
    }

    /**
     * @param LoopInterface $loop
     * @return \React\Promise\Promise
     */
    public function run(LoopInterface $loop)
    {
        $this->deferred = new Deferred();
        $this->loop = $loop;
        $initialSync = [
            'moRefs',
            'hostSystems',
            'virtualMachines',
            'quickStats',
            'perfCounterInfo',
            'storagePods',
            'dataStores',
            'vmDatastoreUsage',
            'vmDiskUsage',
            'vmSnapshots',
            'vmHardware',
            'hostHardware',
            'hostSensors',
        ];

        $schedule = [
            [2, ['eventStream']],
            [900, [
                'moRefs',
                'hostSystems',
                'virtualMachines',
            ]],
            [1800, [
                'vmSnapshots',
                'vmHardware',
            ]],
            [7200, [
                'hostHardware',
                'hostSensors',
            ]],
            [300, [
                'vmDatastoreUsage',
                'vmDiskUsage',
            ]],
            [90, [
                'storagePods',
                'quickStats',
            ]],
        ];

        $this->runTasks($initialSync);
        $loop->addTimer(0.1, function () {
            $this->runNextImmediateTask();
        });
        foreach ($schedule as $pair) {
            $loop->addPeriodicTimer($pair[0], $this->callRunTasks($pair[1]));
        }

        return $this->deferred->promise();
    }

    public function runNextImmediateTask()
    {
        $task = array_shift($this->immediateTasks);
        if ($task) {
            $func = $this->availableTasks[$task];
            try {
                gc_collect_cycles();
                gc_disable();
                $this->emit('beginTask', [$this->taskNames[$task]]);
                $func();
                $this->emit('endTask', [$this->taskNames[$task]]);
                gc_collect_cycles();
                gc_enable();
            } catch (Exception $e) {
                Logger::error("Task $task failed: " . $e->getMessage());
                $this->loop->addTimer(0.5, function () {
                    $this->deferred->reject();
                });
                $this->loop->addTimer(5, function () {
                    $this->runNextImmediateTask();
                });
            }
            $this->loop->addTimer(0.1, function () {
                $this->runNextImmediateTask();
            });
        } else {
            $this->loop->addTimer(1, function () {
                $this->runNextImmediateTask();
            });
        }
    }

    protected function callRunTasks($tasks)
    {
        return function () use ($tasks) {
            $this->runTasks($tasks);
        };
    }

    protected function runTasks($tasks)
    {
        foreach ($tasks as $task) {
            $this->immediateTasks[$task] = $task;
        }
    }

    /**
     * @return EventManager
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\NotFoundError
     * @throws \Zend_Db_Select_Exception
     */
    protected function eventManager()
    {
        if ($this->eventManager === null) {
            $this->eventManager = $this->vCenter->getApi()->eventManager()
                ->persistFor($this->vCenter);
        }

        return $this->eventManager;
    }

    /**
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\NotFoundError
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function streamEvents()
    {
        $cnt = $this->eventManager()->streamToDb();
        if ($cnt < 1000) {
            if ($cnt > 0) {
                Logger::debug('Got %d events', $cnt);
            }
        } else {
            Logger::debug('Got %d event(s), there might be more', $cnt);
        }
    }
}
