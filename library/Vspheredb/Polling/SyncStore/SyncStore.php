<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Psr\Log\LoggerInterface;
use Zend_Db_Adapter_Abstract;

abstract class SyncStore
{
    /** @var Zend_Db_Adapter_Abstract */
    protected Zend_Db_Adapter_Abstract $db;

    /** @var VCenter */
    protected VCenter $vCenter;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param VCenter $vCenter
     * @param LoggerInterface $logger
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, VCenter $vCenter, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->vCenter = $vCenter;
        $this->logger = $logger;
    }

    abstract public function store($result, $class, SyncStats $stats): void;
}
