<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\SimpleDaemon\DaemonTask;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\CustomFieldsManager;
use Icinga\Module\Vspheredb\Polling\SyncStore\SyncStore;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmDatastoreUsageSyncStore;
use Icinga\Module\Vspheredb\Polling\SyncTask\ComputeResourceSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\DatastoreSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostHardwareSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostPhysicalNicSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostQuickStatsSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostSensorSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostSystemSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostVirtualNicSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\ManagedObjectReferenceSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\StoragePodSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\SyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VirtualMachineSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmDatastoreUsageSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmDiskUsageSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmHardwareSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmQuickStatsSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmSnapshotSyncTask;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\ExtendedPromiseInterface;
use function React\Promise\resolve;

class ObjectSync implements DaemonTask
{
    /** @var VCenter */
    protected $vCenter;

    /** @var VsphereApi */
    protected $api;

    /** @var LoopInterface */
    protected $loop;

    /** @var LoggerInterface */
    protected $logger;

    protected $fastTasks = [
        HostQuickStatsSyncTask::class,
        VmQuickStatsSyncTask::class,
    ];

    protected $normalTasks = [
        ManagedObjectReferenceSyncTask::class,
        HostSystemSyncTask::class,
        VirtualMachineSyncTask::class,
        DatastoreSyncTask::class,
        StoragePodSyncTask::class,
        ComputeResourceSyncTask::class,
        VmDiskUsageSyncTask::class,
        VmDatastoreUsageSyncTask::class,
        VmSnapshotSyncTask::class,
    ];

    protected $slowTasks = [
        HostHardwareSyncTask::class,
        HostSensorSyncTask::class,
        HostPhysicalNicSyncTask::class,
        HostVirtualNicSyncTask::class,
        VmHardwareSyncTask::class,
    ];

    /** @var SyncStore[] */
    protected $syncStores = [];

    /** @var TimerInterface[]  */
    protected $timers = [];

    /** @var ExtendedPromiseInterface[] */
    protected $runningTasks = [];

    public function __construct(VCenter $vCenter, VsphereApi $api, LoggerInterface $logger)
    {
        $this->vCenter = $vCenter;
        $this->api = $api;
        $this->logger = $logger;
    }

    public function start(LoopInterface $loop)
    {
        $this->loop = $loop;
        $loop->futureTick(function () {
            $this->initialize();
        });

        return resolve();
    }

    public function stop()
    {
        foreach ($this->timers as $timer) {
            $this->loop->cancelTimer($timer);
        }
        $this->timers = [];
        foreach ($this->runningTasks as $task) {
            // TODO: change how they're being launched, so we can stop them
        }

        return resolve();
    }

    protected function initialize()
    {
        $this->prepareSyncResultHandler()->then(function () {
            $this->scheduleTasks();
        }, function ($e) {
            $this->logger->error($e->getMessage());
        });
    }

    protected function scheduleTasks()
    {
        $this->timers[] = $this->loop->addPeriodicTimer(600, function () {
            // There might be new CustomValue definitions
            $this->prepareSyncResultHandler();
        });
        $this->runAllTasks();
        $this->timers[] = $this->loop->addPeriodicTimer(60, function () {
            $this->runTasks($this->fastTasks);
        });
        $this->timers[] = $this->loop->addPeriodicTimer(170, function () {
            $this->runTasks($this->normalTasks);
        });
        $this->timers[] = $this->loop->addPeriodicTimer(600, function () {
            $this->runTasks($this->slowTasks);
        });
        $this->loop->futureTick(function () {
            $this->refreshOutdatedDatastores();
        });
        $this->timers[] = $this->loop->addPeriodicTimer(300, function () {
            $this->refreshOutdatedDatastores();
        });
    }

    protected function refreshOutdatedDatastores()
    {
        $vms = VmDatastoreUsageSyncStore::fetchOutdatedVms($this->vCenter, 900);
        if (! empty($vms)) {
            VmDatastoreUsageSyncStore::refreshOutdatedVms($this->api, $vms, $this->logger);
        }
    }

    protected function runTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->runTask(new $task);
        }
    }

    protected function runAllTasks()
    {
        $this->runTasks(array_merge($this->fastTasks, $this->normalTasks, $this->slowTasks));
    }

    protected function runTask(SyncTask $task)
    {
        $label = $task->getLabel();
        $idx = get_class($task);
        if (isset($this->runningTasks[$idx])) {
            $this->logger->notice("Task $label is already running, skipping");
            return;
        }
        $this->logger->debug("Running Task $label");
        $this->runningTasks[$idx] = $this->api
            ->fetchBySelectAndPropertySetClass($task->getSelectSetClass(), $task->getPropertySetClass())
            ->then(function ($result) use ($task, $idx) {
                $stats = new SyncStats($task->getLabel());
                $this->requireSyncStoreInstance($task->getSyncStoreClass())
                    ->store($result, $task->getObjectClass(), $stats);
                $this->logger->info($stats->getLogMessage());

                unset($this->runningTasks[$idx]);
                return resolve();
            }, function (Exception $e) use ($idx, $label) {
                $this->logger->error("$label: " . $e->getMessage());
                unset($this->runningTasks[$idx]);
            });
    }

    /**
     * @param $class
     * @return SyncStore
     */
    protected function requireSyncStoreInstance($class)
    {
        if (! isset($this->syncStores[$class])) {
            $this->syncStores[$class] = new $class(
                $this->vCenter->getConnection()->getDbAdapter(),
                $this->vCenter,
                $this->logger
            );
        }

        return $this->syncStores[$class];
    }

    protected function prepareSyncResultHandler()
    {
        return $this->api->fetchCustomFieldsManager()->then(function (CustomFieldsManager $manager = null) {
            $instance = new ObjectSyncStore(
                $this->vCenter->getConnection()->getDbAdapter(),
                $this->vCenter,
                $this->logger,
                $manager
            );
            $this->syncStores[ObjectSyncStore::class] = $instance;
            return resolve();
        });
    }

    protected function fetchEvents()
    {
        /*
        $api->rewindEventCollector()->then(function ($result) use ($api) {
            var_dump($result);
            return $api->readNextEvents();
        })->then(function ($result) {
            var_dump(get_class($result));
            var_dump($result);
        }, function (\Throwable $e) {
            $this->logException($e);
        });
        */
    }
}
