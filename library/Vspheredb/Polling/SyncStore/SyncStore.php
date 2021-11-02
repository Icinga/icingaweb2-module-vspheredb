<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\RemoteSync\SyncStats;
use Psr\Log\LoggerInterface;

abstract class SyncStore
{
    /** @var Adapter|\Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var VCenter */
    protected $vCenter;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     * @param VCenter $vCenter
     * @param LoggerInterface $logger
     */
    public function __construct($db, VCenter $vCenter, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->vCenter = $vCenter;
        $this->logger = $logger;
    }

    abstract public function store($result, $class, SyncStats $stats);
}
