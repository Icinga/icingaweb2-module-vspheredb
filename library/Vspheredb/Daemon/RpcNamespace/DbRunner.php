<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Cli\Process;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Daemon\DbCleanup;
use Icinga\Module\Vspheredb\Db;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;

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

    /** @var LoopInterface */
    protected $loop;

    public function __construct(LoggerInterface $logger, LoopInterface $loop)
    {
        $this->logger = $logger;
        $this->loop = $loop;
        $this->loop->addPeriodicTimer(3600, function () {
            if ($this->connection) {
                try {
                    $this->requireCleanup()->runRegular();
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        });
    }

    /**
     * @param object $config
     * @return bool
     * @throws \Exception
     */
    public function setDbConfigRequest($config)
    {
        try {
            $this->connect($config);
            $this->applyMigrations();
            $this->requireCleanup()->runForStartup();
            Process::setTitle('Icinga::vSphereDB::DB::connected');
        } catch (\Exception $e) {
            Process::setTitle('Icinga::vSphereDB::DB::failing');
            throw $e;
        }

        return true;
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

    protected function connect($config)
    {
        $this->logger->debug('Connecting to DB');
        try {
            $this->disconnect();
        } catch (\Exception $e) {
            // Ignore disconnection errors
        }
        $this->connection = new Db(new ConfigObject((array) $config));
        $this->db = $this->connection->getDbAdapter()->getConnection();
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
        $migrations = Db::migrationsForDb($this->connection);
        if (!$migrations->hasSchema()) {
            if ($migrations->hasAnyTable()) {
                throw new RuntimeException('DB has no vSphereDB schema and is not empty, aborting');
            }
            $this->logger->warning('Database has no schema, will be created');
        }
        if ($migrations->hasPendingMigrations()) {
            Process::setTitle('Icinga::vSphereDB::DB::migration');
            $this->logger->notice('Applying schema migrations');
            $migrations->applyPendingMigrations();
            $this->logger->notice('DB schema is ready');
        }
    }
}
