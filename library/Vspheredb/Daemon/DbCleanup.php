<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Log\Logger;
use gipfl\ZfDb\Adapter\Adapter;
use Psr\Log\LoggerInterface;

class DbCleanup
{
    protected $db;

    /** @var Logger */
    protected $logger;

    /**
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     * @param LoggerInterface $logger
     */
    public function __construct($db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function runForStartup()
    {
        $this->logger->notice('Running DB cleanup (this could take some time)');
        $db = $this->db;
        // Delete all damon entries older than the two most recently running daemons
        $where = <<<WHERE
        ts_last_refresh < (
          SELECT ts_last_refresh FROM (
            SELECT ts_last_refresh
              FROM vspheredb_daemon
             ORDER BY ts_last_refresh DESC
              LIMIT 2
          ) toptwo ORDER BY ts_last_refresh ASC LIMIT 1
        )
WHERE;
        $result = $db->delete('vspheredb_daemon', $where);
        if ($result > 0) {
            $this->logger->info(
                "Removed information related to $result formerly running daemon instance(s)"
            );
        }
        $db->query('OPTIMIZE TABLE vspheredb_daemon')->execute();
        $where = <<<QUERY
        NOT EXISTS (
          SELECT 1 FROM vspheredb_daemon d
           WHERE d.instance_uuid = vspheredb_daemonlog.instance_uuid
        )
QUERY;
        $this->optimizeWhenDeleted($db->delete('vspheredb_daemonlog', $where));
        $this->logger->notice('DB has been cleaned up');
    }

    public function runRegular()
    {
        $this->logger->notice('Running DB cleanup (this could take some time)');
        $db = $this->db;
        $this->optimizeWhenDeleted(
            $db->delete('vspheredb_daemonlog', $db->quoteInto('ts_create < ?', (time() - 86400) * 1000))
        );
        $this->logger->notice('DB has been cleaned up');
    }

    protected function optimizeWhenDeleted($result)
    {
        if ($result > 0) {
            $this->logger->info(
                "Removed $result outdated daemon log lines, optimizing table"
            );
            $this->db->query('OPTIMIZE TABLE vspheredb_daemonlog')->execute();
        }
    }
}
