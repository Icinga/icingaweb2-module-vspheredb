<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\InfluxDb\ChunkedInfluxDbWriter;
use gipfl\SimpleDaemon\DaemonTask;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
use Icinga\Module\Vspheredb\PerformanceData\InfluxConnectionForVcenterLoader;
use Icinga\Module\Vspheredb\PerformanceData\MetricCSVToInfluxDataPoint;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\CounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\CounterMap;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\HostCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\HostNetworkCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\VmCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\VmDiskCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\VmNetworkCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceQuerySpecHelper;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\HostCpuPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\HostMemoryPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\HostNetworkPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\PerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmCpuPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmDiskPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmMemoryPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmNetworkPerformanceSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\PerfCounterInfoSyncStore;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use stdClass;
use Throwable;
use Zend_Db_Adapter_Abstract;

use function React\Promise\resolve;

class PerfDataSync implements DaemonTask
{
    /** @var VCenter */
    protected VCenter $vCenter;

    /** @var VsphereApi */
    protected VsphereApi $api;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var CurlAsync */
    protected CurlAsync $curl;

    /** @var ?ChunkedInfluxDbWriter */
    protected ?ChunkedInfluxDbWriter $influxDbWriter = null;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var TimerInterface[]  */
    protected array $timers = [];

    protected bool $loadingWriterConfig = false;

    /**
     * @param VCenter         $vCenter
     * @param VsphereApi      $api
     * @param CurlAsync       $curl
     * @param LoopInterface   $loop
     * @param LoggerInterface $logger
     */
    public function __construct(
        VCenter $vCenter,
        VsphereApi $api,
        CurlAsync $curl,
        LoopInterface $loop,
        LoggerInterface $logger
    ) {
        $this->vCenter = $vCenter;
        $this->api = $api;
        $this->curl = $curl;
        $this->loop = $loop;
        $this->logger = $logger;
    }

    /**
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function start(LoopInterface $loop): PromiseInterface
    {
        $this->loop = $loop;
        $loop->futureTick(function () {
            $this->initialize();
        });

        return resolve(null);
    }

    /**
     * @return PromiseInterface
     */
    public function stop(): PromiseInterface
    {
        foreach ($this->timers as $timer) {
            $this->loop->cancelTimer($timer);
        }
        $this->timers = [];

        return resolve(null);
    }

    /**
     * @return PromiseInterface
     */
    protected function loadWriterConfig(): PromiseInterface
    {
        if ($this->loadingWriterConfig) {
            return resolve(null);
        }
        $this->loadingWriterConfig = true;
        $loader = InfluxConnectionForVcenterLoader::load($this->vCenter, $this->curl, $this->loop);
        if (! $loader) {
            $this->stopRunningInfluxDbInstances();
            $this->loadingWriterConfig = false;

            return resolve(null);
        }
        return $loader->then(function (?ChunkedInfluxDbWriter $writer) {
            $this->loadingWriterConfig = false;
            $this->stopRunningInfluxDbInstances();
            if (! $writer) {
                return;
            }
            if ($writer instanceof LoggerAwareInterface) { // Compat, older writers do not have this
                $writer->setLogger($this->logger);
            }
            $this->stopRunningInfluxDbInstances();
            $this->influxDbWriter = $writer;
        }, function (Exception $e) {
            $this->loadingWriterConfig = false;
            $this->stopRunningInfluxDbInstances();
            $this->logger->error('Failed to instantiate InfluxDB connection: ' . $e->getMessage());
        });
    }

    /**
     * @return void
     */
    protected function stopRunningInfluxDbInstances(): void
    {
        if ($this->influxDbWriter) {
            $this->influxDbWriter->stop();
            $this->influxDbWriter = null;
        }
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        $this->syncCounterInfo()->then(function () {
            $this->loadWriterConfig();
            $this->scheduleTasks();
        }, function ($e) {
            $this->logger->error($e->getMessage());
        });
    }

    /**
     * @param $spec
     *
     * @return PromiseInterface <PerfEntityMetricCSV[]>
     */
    protected function queryPerf($spec): PromiseInterface
    {
        return $this->api->callOnServiceInstanceObject('perfManager', 'QueryPerf', [
            'querySpec' => $spec
        ])->then(function ($result) {
            if (!isset($result->returnval)) {
                $this->logger->warning('Got no returnval when fetching performance data');

                return [];
            }

            return $result->returnval;
        });
    }

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param UuidInterface            $vCenterUuid
     * @param PerformanceSet           $set
     * @param CounterLookup            $counterLookup
     * @param int|null                 $count
     *
     * @return void
     */
    protected function fetchPerf(
        Zend_Db_Adapter_Abstract $db,
        UuidInterface $vCenterUuid,
        PerformanceSet $set,
        CounterLookup $counterLookup,
        ?int $count
    ): void {
        $tags = $counterLookup->fetchTags($vCenterUuid);
        $counterMap = CounterMap::fetchCounters($db, $set, $vCenterUuid);
        if (empty($counterMap)) {
            $this->logger->notice('Got no counters, nothing to do');

            return;
        }
        $instances = $counterLookup->fetchRequiredMetricInstances($vCenterUuid);
        if (empty($instances)) {
            $this->logger->notice('Got no instances to fetch, nothing to do');

            return;
        }
        $spec = PerformanceQuerySpecHelper::prepareQuerySpec(
            $set->getObjectType(),
            array_keys($counterMap),
            $instances,
            $count
        );
        if ($this->influxDbWriter === null) {
            $this->logger->notice('No more InfluxDB writer available, nothing to do');

            return;
        }

        $this->queryPerf($spec)->then(function ($result) use ($set, $counterMap, $tags) {
            try {
                $cntDataPoints = 0;
                foreach ($result as $r) {
                    foreach (MetricCSVToInfluxDataPoint::map($set->getName(), $r, $counterMap, $tags) as $dataPoint) {
                        $this->influxDbWriter->enqueue($dataPoint);
                        $cntDataPoints++;
                    }
                }
                if ($cntDataPoints) {
                    $this->logger->info("Enqueued $cntDataPoints data points for " . $set->getName());
                }
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage());
            }
        }, function (Exception $e) {
            $this->logger->error($e->getMessage());
        });
    }

    /**
     * @param int|null $count
     *
     * @return void
     */
    protected function sync(?int $count = null): void
    {
        $db = $this->vCenter->getConnection()->getDbAdapter();
        $uuid = Uuid::fromBytes($this->vCenter->getUuid());

        $counterLookup = new VmCounterLookup($db);
        $set = new VmCpuPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);
        $set = new VmMemoryPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);

        $counterLookup = new HostCounterLookup($db);
        $set = new HostCpuPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);
        $set = new HostMemoryPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);

        $counterLookup = new VmNetworkCounterLookup($db);
        $set = new VmNetworkPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);

        $counterLookup = new VmDiskCounterLookup($db);
        $set = new VmDiskPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);

        $counterLookup = new HostNetworkCounterLookup($db);
        $set = new HostNetworkPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);
    }

    /**
     * @return void
     */
    protected function scheduleTasks(): void
    {
        $this->timers[] = $this->loop->addPeriodicTimer(120, function () {
            $this->loadWriterConfig()->then(function () {
                if ($this->influxDbWriter) {
                    try {
                        $this->sync(18);
                    } catch (Throwable $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            });
        });
    }

    /**
     * @return PromiseInterface
     */
    protected function syncCounterInfo(): PromiseInterface
    {
        return $this->api->getServiceInstance()->then(function (ServiceContent $content) {
            return $this->api->fetchSingleObject($content->perfManager);
        })->then(function ($result) {
            $this->storeCounterInfo($result);

            return resolve(null);
        });
    }

    /**
     * @param mixed $result
     *
     * @return void
     */
    protected function storeCounterInfo(mixed $result): void
    {
        $store = new PerfCounterInfoSyncStore(
            $this->vCenter->getConnection()->getDbAdapter(),
            $this->vCenter,
            $this->logger
        );
        $stats = new SyncStats('Performance Counter Info');
        $store->store($result, stdClass::class, $stats);
        $this->logger->info($stats->getLogMessage());
    }
}
