<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\InfluxDb\ChunkedInfluxDbWriter;
use gipfl\InfluxDb\InfluxDbConnection;
use gipfl\InfluxDb\InfluxDbConnectionFactory;
use gipfl\InfluxDb\InfluxDbConnectionV1;
use gipfl\InfluxDb\InfluxDbConnectionV2;
use gipfl\Json\JsonString;
use gipfl\SimpleDaemon\DaemonTask;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
use Icinga\Module\Vspheredb\PerformanceData\MetricCSVToInfluxDataPoint;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\CounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\CounterMap;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\VmDiskCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup\VmNetworkCounterLookup;
use Icinga\Module\Vspheredb\Polling\PerformanceQuerySpecHelper;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\PerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmDiskPerformanceSet;
use Icinga\Module\Vspheredb\Polling\PerformanceSet\VmNetworkPerformanceSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\PerfCounterInfoSyncStore;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use function React\Promise\resolve;

class PerfDataSync implements DaemonTask
{
    /** @var VCenter */
    protected $vCenter;

    /** @var VsphereApi */
    protected $api;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CurlAsync */
    protected $curl;

    /** @var InfluxDbConnection */
    protected $influxDb;

    /** @var ChunkedInfluxDbWriter */
    protected $influxDbWriter;

    /** @var LoopInterface */
    protected $loop;

    /** @var TimerInterface[]  */
    protected $timers = [];

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

        return resolve();
    }

    protected function loadWriterConfig()
    {
        try {
            $this->loadConsumers();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->influxDbWriter = $this->influxDb = null;
        }
    }

    protected function loadConsumers()
    {
        $db = $this->vCenter->getConnection()->getDbAdapter();
        // TODO: not here, not only one of them
        $row = $db->fetchRow($db->select()->from([
            'pc' => 'perfdata_consumer'
        ], 'pc.*')->join([
            'ps' => 'perfdata_subscription'
        ], $db->quoteInto('ps.consumer_uuid = pc.uuid AND ps.vcenter_uuid = ?', $this->vCenter->get('instance_uuid')), [
            'vcenter_settings' => 'ps.settings'
        ]));
        if (! $row) {
            $this->influxDb = null;
            if ($this->influxDbWriter) {
                $this->influxDbWriter->stop();
                $this->influxDbWriter = null;
            }
            return;
        }
        $vCenterSettings = JsonString::decode($row->vcenter_settings);
        unset($row->vcenter_settings);
        $instance = PerfDataConsumerHook::createConsumerInstance(
            PerfdataConsumer::create((array) $row),
            $this->loop
        );
        switch ($instance->getSetting('api_version')) {
            case 'v1':
                $connection = new InfluxDbConnectionV1(
                    $this->curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('username'),
                    $instance->getSetting('password')
                );
                break;
            case 'v2':
                $connection = new InfluxDbConnectionV2(
                    $this->curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('organization'),
                    $instance->getSetting('token')
                );
                break;
            default:
                $connection = InfluxDbConnectionFactory::create(
                    $this->curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('username'),
                    $instance->getSetting('password')
                );
        }

        $this->influxDb = $connection;
        $this->influxDbWriter = new ChunkedInfluxDbWriter(
            $this->influxDb,
            $vCenterSettings->dbname,
            $this->loop
        );
        if ($this->influxDbWriter instanceof LoggerAwareInterface) { // Compat, older writers do not have this
            $this->influxDbWriter->setLogger($this->logger);
        }
        $this->influxDbWriter->setPrecision('s');
    }

    protected function initialize()
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
     * @return \React\Promise\PromiseInterface <PerfEntityMetricCSV[]>
     */
    protected function queryPerf($spec)
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

    protected function fetchPerf(
        $db,
        UuidInterface $vCenterUuid,
        PerformanceSet $set,
        CounterLookup $counterLookup,
        $count
    ) {
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
        }, function (Exception $e) {
            $this->logger->error($e->getMessage());
        });
    }

    protected function sync($count = null)
    {
        $db = $this->vCenter->getConnection()->getDbAdapter();
        $uuid = Uuid::fromBytes($this->vCenter->getUuid());

        $counterLookup = new VmNetworkCounterLookup($db);
        $set = new VmNetworkPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);

        $counterLookup = new VmDiskCounterLookup($db);
        $set = new VmDiskPerformanceSet();
        $this->fetchPerf($db, $uuid, $set, $counterLookup, $count);
    }

    protected function scheduleTasks()
    {
        $this->timers[] = $this->loop->addPeriodicTimer(120, function () {
            $this->loadWriterConfig();
            if ($this->influxDbWriter) {
                $this->sync(18);
            }
        });
        $this->loop->futureTick(function () {
            $this->loadWriterConfig();
            if ($this->influxDbWriter) {
                $this->sync(18); // Used to be 180 (= 1hour, reduced to fix problems with slow systems)
            }
        });
    }

    protected function syncCounterInfo()
    {
        return $this->api->getServiceInstance()->then(function (ServiceContent $content) {
            return $this->api->fetchSingleObject($content->perfManager);
        })->then(function ($result) {
            $this->storeCounterInfo($result);
            return resolve();
        });
    }

    protected function storeCounterInfo($result)
    {
        $store = new PerfCounterInfoSyncStore(
            $this->vCenter->getConnection()->getDbAdapter(),
            $this->vCenter,
            $this->logger
        );
        $stats = new SyncStats('Performance Counter Info');
        $store->store($result, \stdClass::class, $stats);
        $this->logger->info($stats->getLogMessage());
    }
}
