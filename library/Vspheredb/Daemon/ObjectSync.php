<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\SimpleDaemon\DaemonTask;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\CustomFieldsManager;
use Icinga\Module\Vspheredb\Polling\RestApi;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmDatastoreUsageSyncStore;
use Icinga\Module\Vspheredb\Polling\SyncTask\ComputeResourceSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\DatastoreSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostHardwareSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostHbaSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostPhysicalNicSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostQuickStatsSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostSensorSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostSystemSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\HostVirtualNicSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\ManagedObjectReferenceSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\RestApiTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\StandaloneTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\StoragePodSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\SyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\TaggingCategorySyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\TaggingObjectTagSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\TaggingTagSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VirtualMachineSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmDatastoreUsageSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmDiskUsageSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmEventHistorySyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmHardwareSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmQuickStatsSyncTask;
use Icinga\Module\Vspheredb\Polling\SyncTask\VmSnapshotSyncTask;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
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
        HostHbaSyncTask::class,
        HostPhysicalNicSyncTask::class,
        HostVirtualNicSyncTask::class,
        VmHardwareSyncTask::class,
    ];

    protected $taggingTasks = [
        TaggingTagSyncTask::class,
        TaggingObjectTagSyncTask::class,
        TaggingCategorySyncTask::class,
    ];

    /** @var TimerInterface[]  */
    protected $timers = [];

    /** @var PromiseInterface[] */
    protected $runningTasks = [];

    protected $ready = false;

    /** @var DbProcessRunner */
    protected $dbRunner;

    /** @var RestApi */
    protected $restApi;

    public function __construct(
        VCenter $vCenter,
        VsphereApi $api,
        RestApi $restApi,
        DbProcessRunner $dbRunner,
        LoggerInterface $logger
    ) {
        $this->vCenter = $vCenter;
        if ($vCenter->isHostAgent()) {
            $this->removeVCenterOnlyTasks();
        }
        $this->api = $api;
        $this->restApi = $restApi;
        $this->logger = $logger;
        $this->dbRunner = $dbRunner;
    }

    protected function removeVCenterOnlyTasks()
    {
        $this->normalTasks = array_filter($this->normalTasks, function ($task) {
            return $task !== StoragePodSyncTask::class;
        });
    }

    public function start(LoopInterface $loop)
    {
        $this->loop = $loop;
        return $this->initialize();
    }

    public function stop()
    {
        $this->ready = false;
        foreach ($this->timers as $timer) {
            $this->loop->cancelTimer($timer);
        }
        $this->timers = [];
        foreach ($this->runningTasks as $task) {
            // TODO: change how they're being launched, so we can stop them
        }
        $this->runningTasks = [];

        return resolve();
    }

    protected function initialize()
    {
        return $this->prepareSyncResultHandler()->then(function () {
            return $this->prepareEventPolling();
        })->then(function () {
            $this->ready = true;
            $this->scheduleTasks();
        }, function ($e) {
            $this->logger->error($e->getMessage());
            throw $e;
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
        $this->timers[] = $this->loop->addPeriodicTimer(300, function () {
            // Disabled for now, VMware-admins do not want to see tasks
            // $this->refreshOutdatedDatastores();
        });
        $this->timers[] = $this->loop->addPeriodicTimer(2, function () {
            $this->runTasks([VmEventHistorySyncTask::class]);
        });
        $this->timers[] = $this->loop->addPeriodicTimer(400, function () {
            $this->restApi->requireSession()->then(function () {
                $this->runTasks($this->taggingTasks);
            });
        });
    }

    protected function refreshOutdatedDatastores()
    {
        $idx = VmDatastoreUsageSyncStore::class;
        $label = 'Refresh outdated VMs';
        if (isset($this->runningTasks[$idx])) {
            $this->logger->notice("Task '$label' is already running, skipping");
            return;
        }
        // $this->logger->debug("Running Task '$label'");

        $maxCount = 30;
        $total = count(VmDatastoreUsageSyncStore::fetchOutdatedVms($this->vCenter, 3600 * 6));
        $vms = VmDatastoreUsageSyncStore::fetchOutdatedVms($this->vCenter, 3600 * 6, $maxCount);
        $count = count($vms);
        if ($count > 0) {
            $this->logger->debug("Refreshing  '$label': $count out of $total VMs with outdated storage information");
            $this->runningTasks[$idx] = VmDatastoreUsageSyncStore::refreshOutdatedVms($this->api, $vms, $this->logger)
                ->then(function () use ($idx) {
                    unset($this->runningTasks[$idx]);
                });
        }
    }

    protected function runTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->runTask(new $task());
        }
    }

    protected function runAllTasks()
    {
        $this->runTasks(array_merge($this->fastTasks, $this->normalTasks, $this->slowTasks));
        $this->restApi->requireSession()->then(function () {
            $this->runTasks($this->taggingTasks);
        });
    }

    protected function runTask(SyncTask $task)
    {
        $label = $task->getLabel();
        $idx = get_class($task);
        if (isset($this->runningTasks[$idx])) {
            $this->logger->notice("Task '$label' is already running, skipping");
            return;
        }
        // $this->logger->debug("Running Task '$label'");

        if ($task instanceof StandaloneTask) {
            $instance = $task->run($this->api, $this->logger);
        } elseif ($task instanceof RestApiTask) {
            $instance = $task->run($this->restApi);
        } else {
            $instance = $this->api->fetchBySelectAndPropertySetClass(
                $task->getSelectSetClass(),
                $task->getPropertySetClass()
            );
        }

        $this->runningTasks[$idx] = $instance->then(function ($result) use ($task, $idx) {
            if (! $this->ready) {
                $this->logger->warning(sprintf(
                    "Not storing result for '%s', task has been stopped",
                    $task->getLabel()
                ));
                unset($this->runningTasks[$idx]);
                return resolve();
            }
            $task->tweakResult($result);

            return $this->dbRunner->request('db.processSyncTaskResult', [
                'vCenterId'   => (int) $this->vCenter->get('id'),
                'result'      => $result,
                'taskLabel'   => $task->getLabel(),
                'storeClass'  => $task->getSyncStoreClass(),
                'objectClass' => $task->getObjectClass(),
            ])->then(function ($stats) use ($idx) {
                $stats = SyncStats::fromSerialization($stats);
                if ($stats->hasChanges()) {
                    $this->logger->info($stats->getLogMessage());
                }

                unset($this->runningTasks[$idx]);
                return resolve();
            }, function (Exception $e) use ($idx) {
                unset($this->runningTasks[$idx]);
                $this->logger->error($e->getMessage());

                return reject($e);
            });
        }, function (Exception $e) use ($idx, $label) {
            $this->logger->error("$label: " . $e->getMessage());
            unset($this->runningTasks[$idx]);
        });
    }

    protected function prepareEventPolling()
    {
        return $this->dbRunner->request('db.getLastEventTimeStamp', [
            'vCenterId' => $this->vCenter->get('id')
        ])->then(function ($lastTimestamp) {
            $this->api->setLastEventTimestamp($lastTimestamp);
        });
    }

    /**
     * Refreshes the custom fields map in the DB process
     *
     * @return \React\Promise\PromiseInterface
     */
    protected function prepareSyncResultHandler()
    {
        return $this->api->fetchCustomFieldsManager()->then(function (?CustomFieldsManager $manager = null) {
            if ($manager === null) {
                return resolve();
            }

            return $this->dbRunner->request('db.setCustomFieldsMap', [
                'vCenterId' => $this->vCenter->get('id'),
                'map'       => $manager->requireMap(),
            ]);
        });
    }
}
