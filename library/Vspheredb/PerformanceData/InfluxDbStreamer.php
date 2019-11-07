<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use Clue\React\Buzz\Message\ResponseException;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\MappedClass\PerfEntityMetricCSV;
use Icinga\Module\Vspheredb\PerformanceData\InfluxDb\AsyncInfluxDbWriter;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\PerformanceSet;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\VmDisks;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\VmNetwork;
use Icinga\Module\Vspheredb\Rpc\Logger;
use React\EventLoop\LoopInterface;

class InfluxDbStreamer
{
    /** @var VCenter */
    protected $vCenter;

    /** @var LoopInterface $loop */
    protected $loop;

    /** @var AsyncInfluxDbWriter */
    protected $influx;

    protected $idle = true;

    protected $baseUrl;

    protected $dbName;

    protected $fetchedMetrics = 0;

    protected $sentLines = 0;

    protected $linesWaitingForInflux = 0;

    protected $pendingLines = 0;

    protected $maxPendingLines = 5000;

    protected $queue = [];

    public function __construct(VCenter $vCenter, LoopInterface $loop)
    {
        $this->vCenter = $vCenter;
        $this->loop = $loop;
    }

    /**
     * @param $baseUrl
     * @param $dbName
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function streamTo($baseUrl, $dbName)
    {
        Logger::info("Streaming to $baseUrl");
        if ($this->influx !== null) {
            throw new \RuntimeException('Cannot start to stream while streaming');
        }
        $this->influx = new AsyncInfluxDbWriter($baseUrl, $this->loop);
        $this->idle = false;

        $sets = [
            'VmNetwork' => VmNetwork::class,
            // 'VmDisks'   => VmDisks::class,
        ];

        foreach ($sets as $set) {
            $this->loop->futureTick(function () use ($set, $dbName) {
                $this->streamSet(new $set($this->vCenter), $dbName);
            });
        }
    }

    /**
     * @param PerformanceSet $performanceSet
     * @param $dbName
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function streamSet(PerformanceSet $performanceSet, $dbName)
    {
        $this->loop->addPeriodicTimer(3, function () use ($dbName) {
            $this->sendNextBatch($dbName);
        });
        $counters = $performanceSet->getCounters();
        $mapper = new PerfMetricMapper($counters);
        /** @var PerfEntityMetricCSV $metric */
        $tags = $performanceSet->fetchObjectTags();
        $metrics = $performanceSet->fetch();
        while ($this->pendingLines < $this->maxPendingLines && $metrics->valid()) {
            $metric = $metrics->current();
            $this->fetchedMetrics += count($metric->value);
            $this->queue[] = $mapper->makeInfluxDataPoints(
                $metric,
                $performanceSet->getMeasurementName(),
                $tags
            );
            // Logger::info('Flushing queue');
            // $this->flushQueue($dbName);
            $metrics->next();
        }

        // $this->flushQueue($dbName, true);
    }

    protected function sendNextBatch($dbName)
    {
        Logger::info('send has been triggered');
        if (empty($this->queue)) {
            Logger::info('queue is empty');
            return;
        }

        $batch = array_shift($this->queue);
        $lines = [];
        $lines = array_merge($lines, $batch);

        $linesWaitingForInflux = count($batch);
        $this->influx->send($dbName, $batch)->then(function () use (& $linesWaitingForInflux, $dbName) {
            Logger::info(sprintf(
                'Sent %d lines to InfluxDB',
                $linesWaitingForInflux
            ));
            $this->loop->futureTick(function () use ($dbName) {
                $this->sendNextBatch($dbName);
            });
        })->otherwise(function (\Exception $e) use (& $linesWaitingForInflux) {
            Logger::error(sprintf(
                'Failed to send %d lines to InfluxDB: %s',
                $linesWaitingForInflux,
                $e->getMessage()
            ));
            if ($e instanceof ResponseException) {
                Logger::error($e->getResponse()->getBody());
            }
        })->always(function () use (& $linesWaitingForInflux) {
            $linesWaitingForInflux = 0;
        });
        // $this->linesWaitingForInflux = $this->pendingLines;
        // $this->pendingLines = 0;
    }

    protected function flushQueue($dbName, $force = false)
    {
        if (empty($this->queue)) {
            return;
        }
        if ($force || $this->pendingLines >= $this->maxPendingLines) {
            $batch = [];
            foreach ($this->queue as $p1) {
                foreach ($p1 as $p2) {
                    $batch[] = $p2;
                }
            }
            $this->linesWaitingForInflux = count($batch);
            $this->influx->send($dbName, $batch)->then(function () {
                Logger::debug(sprintf(
                    'Sent %d lines to InfluxDB',
                    $this->linesWaitingForInflux
                ));
            })->otherwise(function (\Exception $e) {
                Logger::debug(sprintf(
                    'Failed to send %d lines to InfluxDB: %s',
                    $this->linesWaitingForInflux,
                    $e->getMessage()
                ));
            })->always(function () {
                $this->linesWaitingForInflux = 0;
            });
            $this->queue = [];
            $this->linesWaitingForInflux = $this->pendingLines;
            $this->pendingLines = 0;
        }
    }

    public function isIdle()
    {
        return $this->idle;
    }
}
