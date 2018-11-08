<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
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

    /**
     * SyncRunner constructor.
     * @param VCenter $vCenter
     */
    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
        $this->availableTasks = [
            'moRefs' => function () {
                Logger::info('Task: moRefs');
                (new SyncManagedObjectReferences($this->vCenter))->sync();
            },
            'quickStats' => function () {
                Logger::info('Task: quickStats');
                (new SyncQuickStats($this->vCenter))->run();
            },
            'hostSystems' => function () {
                Logger::info('Task: hostSystems');
                HostSystem::syncFromApi($this->vCenter);
            },
            'virtualMachines' => function () {
                Logger::info('Task: virtualMachines');
                VirtualMachine::syncFromApi($this->vCenter);
            },
            'dataStores' => function () {
                Logger::info('Task: dataStores');
                Datastore::syncFromApi($this->vCenter);
            },
            'hostHardware' => function () {
                Logger::info('Task: hostHardware');
                (new SyncHostHardware($this->vCenter))->run();
            },
            'hostSensors' => function () {
                Logger::info('Task: hostSensors');
                (new SyncHostSensors($this->vCenter))->run();
            },
            'vmHardware' => function () {
                Logger::info('Task: vmHardware');
                (new SyncVmHardware($this->vCenter))->run();
            },
            'vmDiskUsage' => function () {
                Logger::info('Task: vmDiskUsage');
                (new SyncVmDiskUsage($this->vCenter))->run();
            },
            'vmDatastoreUsage' => function () {
                Logger::info('Task: vmDatastoreUsage');
                (new SyncVmDatastoreUsage($this->vCenter))->run();
            },
            'vmSnapshots' => function () {
                Logger::info('Task: vmSnapshots');
                (new SyncVmSnapshots($this->vCenter))->run();
            },
            'eventStream' => function () {
                Logger::info('Task: eventStream');
                $this->streamEvents();
            },
            'perfCounters' => function () {
                Logger::info('Task: perfCounters');
                (new SyncPerfCounters($this->vCenter))->run();
            },
            'perfCounterInfo' => function () {
                Logger::info('Task: perfCounterInfo');
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
            'dataStores',
            'vmDatastoreUsage',
            'vmDiskUsage',
            'vmSnapshots',
            'vmHardware',
            'hostHardware',
            'hostSensors',
            // 'perfCounterInfo',
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
                $func();
                gc_collect_cycles();
                gc_enable();
            } catch (\Exception $e) {
                Logger::error($e);
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
            Logger::debug('Got %d events', $cnt);
        } else {
            Logger::debug('Got %d event(s), there might be more', $cnt);
        }
    }
}
