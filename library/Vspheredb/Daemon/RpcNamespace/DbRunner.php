<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Exception;
use gipfl\Cli\Process;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Application\MemoryLimit;
use Icinga\Module\Vspheredb\Daemon\DbCleanup;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\VCenterCleanup;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Monitoring\PersistedRuleProblems;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;
use Icinga\Module\Vspheredb\Polling\SyncStore\SyncStore;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmEventHistorySyncStore;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Provides RPC methods
 */
class DbRunner
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Db */
    protected $connection;

    protected $db;

    /** @var ?DbCleanup */
    protected $runningVcenterDeletion = null;

    /** @var LoopInterface */
    protected $loop;

    protected $vCenters = [];

    /**
     * @var array vCenterId -> [ SyncStoreClassName => SyncStore ]
     */
    protected $vCenterSyncStores = [];

    public function __construct(LoggerInterface $logger, LoopInterface $loop)
    {
        MemoryLimit::raiseTo('1024M');
        $this->logger = $logger;
        $this->loop = $loop;
        $this->loop->addPeriodicTimer(3600, function () {
            if ($this->connection) {
                try {
                    $this->requireCleanup()->runRegular();
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        });
        $this->loop->addPeriodicTimer(90, function () {
            if ($this->connection) {
                try {
                    $this->refreshMonitoringRuleProblemsRequest();
                } catch (\Throwable $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        });
    }

    /**
     * @param object $config
     * @return PromiseInterface
     * @throws Exception
     */
    public function setDbConfigRequest($config)
    {
        try {
            $this->vCenters = [];
            $this->vCenterSyncStores = [];
            Process::setTitle('Icinga::vSphereDB::DB::connecting');
            $this->connect($config);
            $deferred = new Deferred();
            $this->loop->futureTick(function () use ($deferred) {
                Process::setTitle('Icinga::vSphereDB::DB::migration');
                $this->applyMigrations()->then(function () use ($deferred) {
                    try {
                        $this->requireCleanup()->runForStartup();
                        $this->setProcessReadyTitle();
                    } catch (Exception $e) {
                        Process::setTitle('Icinga::vSphereDB::DB::failing');
                        $deferred->reject($e);
                    }
                    $deferred->resolve(null);
                }, function (Exception $e) use ($deferred) {
                    $deferred->reject($e);
                });
            });

            return $deferred->promise();
        } catch (Exception $e) {
            Process::setTitle('Icinga::vSphereDB::DB::failing');
            throw $e;
        }
    }

    protected function setProcessReadyTitle()
    {
        Process::setTitle('Icinga::vSphereDB::DB::connected');
    }

    /**
     * @return bool
     */
    public function runDbCleanupRequest()
    {
        $this->requireCleanup()->runForStartup();
        return true;
    }

    /**
     * @return bool
     */
    public function clearDbConfigRequest()
    {
        $this->disconnect();
        return true;
    }

    /**
     * @return bool
     */
    public function hasPendingMigrationsRequest()
    {
        if ($this->connection === null) {
            throw new RuntimeException('Unable to determine migration status, have no DB connection');
        }

        return Db::migrationsForDb($this->connection)->hasPendingMigrations();
    }

    /**
     * @param int $vCenterId
     * @param array $map
     * @return bool
     */
    public function setCustomFieldsMapRequest($vCenterId, $map)
    {
        $vCenter = $this->requireVCenter($vCenterId);
        $this->vCenterSyncStores[$vCenterId][ObjectSyncStore::class] = new ObjectSyncStore(
            $vCenter->getConnection()->getDbAdapter(),
            $vCenter,
            $this->logger,
            $map
        );

        return true;
    }

    /**
     * @param int $vCenterId
     * @return int
     * @throws \Icinga\Exception\NotFoundError
     */
    public function getLastEventTimeStampRequest($vCenterId)
    {
        return VmEventHistorySyncStore::selectLast(
            $this->db,
            $this->requireVCenter($vCenterId)->getUuid(),
            'ts_event_ms'
        );
    }

    /**
     * @param int $vCenterId
     * @param array $result
     * @param string $taskLabel
     * @param string $storeClass
     * @param string $objectClass
     * @return SyncStats
     */
    public function processSyncTaskResultRequest($vCenterId, $result, $taskLabel, $storeClass, $objectClass)
    {
        Process::setTitle('Icinga::vSphereDB::DB::Storing ' . $taskLabel);

        $stats = new SyncStats($taskLabel);
        try {
            $this->requireSyncStoreForVCenterInstance($vCenterId, $storeClass)
                ->store($result, $objectClass, $stats);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Task %s failed. %s: %s (%d)',
                $taskLabel,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
        $this->setProcessReadyTitle();

        return $stats;
    }

    public function refreshMonitoringRuleProblemsRequest()
    {
        if ($this->connection === null) {
            $this->logger->warning('Not refreshing Rule problems, DB is not ready');
            return false;
        }

        if (Db::migrationsForDb($this->connection)->hasPendingMigrations()) {
            $this->logger->warning('Not refreshing Rule problems, DB is not ready');
            return false;
        }

        try {
            $start = microtime(true);
            $p = new PersistedRuleProblems($this->connection);
            $p->refresh();
            $duration = microtime(true) - $start;
            $this->logger->debug(sprintf('Refreshing Monitoring Rule problems took %.2Fs', $duration));

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Refreshing Rule Problems failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int $vCenterId
     * @return bool
     */
    public function deleteVcenterRequest($vCenterId)
    {
        $this->logger->notice('Got db.deleteVcenter for id=' . $vCenterId);
        if ($this->connection === null) {
            throw new RuntimeException('Unable to remove vCenter, have no DB connection');
        }
        if ($this->runningVcenterDeletion !== null) {
            throw new RuntimeException(
                'Unable to remove vCenter, a cleanup is in progress'
            );
        }

        Process::setTitle('Icinga::vSphereDB::DB::deleteVcenter ' . $vCenterId);
        $this->runningVcenterDeletion = new VCenterCleanup($this->connection, $vCenterId);
        $this->runningVcenterDeletion->run()->then(function () {
            $this->runningVcenterDeletion = null;
            $this->setProcessReadyTitle();
        }, function (Exception $e) {
            $this->runningVcenterDeletion = null;
            $this->logger->error('db.deleteVcenter: ' . $e->getMessage());
        });

        return true;
    }

    /**
     * @param $vCenterId
     * @param $class
     * @return SyncStore
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function requireSyncStoreForVCenterInstance($vCenterId, $class)
    {
        $vCenter = $this->requireVCenter($vCenterId);
        if (! isset($this->vCenterSyncStores[$vCenterId])) {
            $this->vCenterSyncStores[$vCenterId] = [];
        }
        if (! isset($this->vCenterSyncStores[$vCenterId][$class])) {
            $this->vCenterSyncStores[$vCenterId][$class] = new $class(
                $this->db,
                $vCenter,
                $this->logger
            );
        }

        return $this->vCenterSyncStores[$vCenterId][$class];
    }

    /**
     * @param $id
     * @return VCenter
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function requireVCenter($id)
    {
        if (! isset($this->vCenters[$id])) {
            $this->vCenters[$id] = VCenter::loadWithAutoIncId($id, $this->connection);
        }

        return $this->vCenters[$id];
    }

    protected function connect($config)
    {
        $this->logger->debug('Connecting to DB');
        try {
            $this->disconnect();
        } catch (Exception $e) {
            // Ignore disconnection errors
        }
        $this->connection = new Db(new ConfigObject((array) $config));
        $this->db = $this->connection->getDbAdapter();
        $this->db->getConnection();
    }

    protected function disconnect()
    {
        if ($this->connection) {
            $this->connection->getDbAdapter()->closeConnection();
            $this->connection = null;
            $this->db = null;
        }
    }

    protected function requireCleanup()
    {
        if ($this->connection === null) {
            throw new RuntimeException('Cannot run DB cleanup w/o DB connection');
        }
        $c = new DbCleanup($this->connection->getDbAdapter(), $this->logger);
        Process::setTitle('Icinga::vSphereDB::DB::cleanup');
        return $c;
    }

    protected function applyMigrations()
    {
        try {
            $migrations = Db::migrationsForDb($this->connection);
            if (!$migrations->hasSchema()) {
                if ($migrations->hasAnyTable()) {
                    throw new RuntimeException('DB has no vSphereDB schema and is not empty, aborting');
                }
                $this->logger->warning('Database has no schema, will be created');
            }
            $hasMigrations = $migrations->hasPendingMigrations();
        } catch (Exception $e) {
            return reject($e);
        }
        $deferred = new Deferred();
        if ($hasMigrations) {
            Process::setTitle('Icinga::vSphereDB::DB::migration');
            $this->logger->notice('Applying schema migrations');
            $this->loop->futureTick(function () use ($migrations, $deferred) {
                try {
                    $migrations->applyPendingMigrations();
                } catch (Exception $e) {
                    $deferred->reject($e);
                }
                $this->logger->notice('DB schema is ready');
                $deferred->resolve(null);
            });
        } else {
            return resolve();
        }

        return $deferred->promise();
    }
}
