<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Cli\Process;
use gipfl\Protocol\JsonRpc\Error;
use gipfl\Protocol\JsonRpc\Notification;
use gipfl\Protocol\JsonRpc\PacketHandler;
use gipfl\Protocol\JsonRpc\Request;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Db;
use Psr\Log\LoggerInterface;

class DbRunner implements PacketHandler
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Db */
    protected $connection;

    protected $db;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        /// $logger->info('DDDDD');
    }

    protected function connect($config)
    {
        $this->connection = new Db(new ConfigObject((array) $config));
        $this->db = $this->connection->getDbAdapter()->getConnection();
    }

    public function handle(Notification $notification)
    {
        try {
            switch ($notification->getMethod()) {
                case 'vspheredb.setDbConfig':
                    try {
                        $this->connect($notification->getParam('config'));
                        Process::setTitle('Icinga::vSphereDB::DB::connected');
                    } catch (\Exception $e) {
                        Process::setTitle('Icinga::vSphereDB::DB::failing');
                        return Error::forException($e);
                    }

                    return true;
                case 'vspheredb.runDbCleanup':
                    if ($this->connection === null) {
                        return Error::forException(new \RuntimeException('Cannot run DB cleanup w/o DB connection'));
                    }
                    try {
                        $c = new DbCleanup($this->connection->getDbAdapter(), $this->logger);
                        $c->run();
                        return true;
                    } catch (\Exception $e) {
                        return Error::forException($e);
                    }
                    break;
                case 'vspheredb.clearDbConfig':
                    return 'Cleared';
                case 'vspheredb.syncMoRefs':
                    return true;
                case 'vspheredb.syncManagedObjects':
                    return true;
            }
        } catch (\Exception $e) {
            if ($notification instanceof Request) {
                return $e;
            }
            $this->logger->error($e->getMessage() . $e->getTraceAsString());
        } catch (\Throwable $e) {
            if ($notification instanceof Request) {
                return $e;
            }
            $this->logger->error($e->getMessage() . $e->getTraceAsString());
        }

        return new Error(Error::METHOD_NOT_FOUND);
    }
}
