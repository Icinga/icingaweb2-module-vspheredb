<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Cli\Process;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Db;
use Psr\Log\LoggerInterface;
use RuntimeException;

class DbRunner
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Db */
    protected $connection;

    protected $db;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function connect($config)
    {
        $this->connection = new Db(new ConfigObject((array) $config));
        $this->db = $this->connection->getDbAdapter()->getConnection();
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
        if ($this->connection === null) {
            return throw new RuntimeException('Cannot run DB cleanup w/o DB connection');
        }
        $c = new DbCleanup($this->connection->getDbAdapter(), $this->logger);
        $c->run();

        return true;
    }

    /**
     * @return bool
     */
    public function clearDbConfigRequest()
    {
        if ($this->connection) {
            $this->connection->getDbAdapter()->closeConnection();
            $this->connection = null;
        }
        return true;
    }
}
