<?php

namespace Icinga\Module\Vspheredb;

use Exception;
use Icinga\Application\Logger;
use Icinga\Application\Platform;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Sync\SyncHostHardware;
use Icinga\Module\Vspheredb\Sync\SyncHostSensors;
use Icinga\Module\Vspheredb\Sync\SyncManagedObjectReferences;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounterInfo;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounters;
use Icinga\Module\Vspheredb\Sync\SyncQuickStats;
use Icinga\Module\Vspheredb\Sync\SyncVmDatastoreUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmDiskUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmHardware;
use React\EventLoop\Factory as Loop;

class MainRunner
{
    /** @var Loop */
    private $loop;

    /** @var int */
    protected $vCenterId;

    private $isReady = false;

    /** @var VCenter */
    protected $vCenter;

    /** @var Db */
    protected $connection;

    /** @var EventManager */
    protected $eventManager;

    /** @var bool */
    protected $useDefaultConnection = true;

    /**
     * MainRunner constructor.
     * @param $vCenterId
     */
    public function __construct($vCenterId)
    {
        $this->vCenterId = $vCenterId;
    }

    public function setConnection(Db $connection)
    {
        $this->connection = $connection;
        $this->useDefaultConnection = false;

        return $this;
    }

    public function run()
    {
        $loop = $this->loop = Loop::create();

        $loop->nextTick(function () {
            $this->isReady = true;
            $this->runFailSafe(function () {
                $this->initialize();

                // TODO: We need better scheduling
                $this->syncAllObjects();
                $this->syncVmHardware();
                $this->syncHostHardware();
                $this->syncHostSensors();
            });
        });
        $loop->addPeriodicTimer(900, function () {
            $this->runFailSafe(function () {
                $this->syncAllObjects();
            });
        });
        $loop->addPeriodicTimer(1800, function () {
            $this->runFailSafe(function () {
                $this->syncVmHardware();
            });
        });
        $loop->addPeriodicTimer(7200, function () {
            $this->runFailSafe(function () {
                $this->syncHostHardware();
                $this->syncHostSensors();
            });
        });
        $loop->addPeriodicTimer(120, function () {
            $this->runFailSafe(function () {
                $this->syncVmDiskUsage();
                $this->syncVmDatastoreUsage();
            });
        });
        $loop->addPeriodicTimer(90, function () {
            $this->runFailSafe(function () {
                $this->syncQuickStatsAction();
            });
        });
        $loop->addPeriodicTimer(5, function () {
            $this->runFailSafe(function () {
                $this->refreshMyState();
            });
        });
        $loop->addPeriodicTimer(15, function () {
            if (! $this->isReady) {
                $this->reset();
            }
        });
        $loop->addPeriodicTimer(5, function () {
            $this->runFailSafe(function () {
                $this->streamEvents();
            });
        });
        $loop->run();
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    protected function initialize()
    {
        Logger::debug('MainRunner::initialize()');
        if ($this->useDefaultConnection) {
            $this->connection = null;
            $this->connection = Db::newConfiguredInstance();
        }

        $this->vCenter = VCenter::loadWithAutoIncId(
            $this->vCenterId,
            $this->connection
        );

        $this->eventManager = $this->vCenter->getApi()->eventManager()
            ->persistFor($this->vCenter);

        Logger::info(
            'Loaded VCenter information for %s from DB',
            $this->getLogName()
        );

        $this->updateMyState();
    }

    /**
     * @throws \Icinga\Exception\IcingaException
     */
    public function syncAllObjects()
    {
        $this->syncObjectReferences();
        HostSystem::syncFromApi($this->vCenter);
        VirtualMachine::syncFromApi($this->vCenter);
        Datastore::syncFromApi($this->vCenter);
    }

    protected function syncObjectReferences()
    {
        (new SyncManagedObjectReferences($this->vCenter))->sync();
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function refreshMyState()
    {
        $db = $this->connection->getDbAdapter();
        $updated = $db->update('vcenter_sync', [
            'ts_last_refresh' => Util::currentTimestamp()
        ], $db->quoteInto('vcenter_uuid = ?', $this->vCenter->getUuid()));

        if (! $updated) {
            $this->insertMyState();
        }
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function updateMyState()
    {
        $db = $this->connection->getDbAdapter();
        $updated = $db->update('vcenter_sync', [
            'ts_last_refresh' => Util::currentTimestamp(),
            'pid'             => posix_getpid(),
            'fqdn'            => Platform::getFqdn(),
            'username'        => Platform::getPhpUser(),
            'php_version'     => Platform::getPhpVersion(),
        ], $db->quoteInto('vcenter_uuid = ?', $this->vCenter->getUuid()));

        if (! $updated) {
            $this->insertMyState();
        }
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function insertMyState()
    {
        $db = $this->connection->getDbAdapter();
        $db->insert('vcenter_sync', [
            'vcenter_uuid'    => $this->vCenter->getUuid(),
            'ts_last_refresh' => Util::currentTimestamp(),
            'pid'             => posix_getpid(),
            'fqdn'            => Platform::getFqdn(),
            'username'        => Platform::getPhpUser(),
            'php_version'     => Platform::getPhpVersion(),
        ]);
    }

    public function syncPerfCounters()
    {
        $sync = new SyncPerfCounters($this->vCenter);
        $sync->run();
    }

    public function syncPerfCounterInfo()
    {
        $sync = new SyncPerfCounterInfo($this->vCenter);
        $sync->run();
    }

    public function syncQuickStatsAction()
    {
        $sync = new SyncQuickStats($this->vCenter);
        $sync->run();
    }

    public function syncHostHardware()
    {
        $sync = new SyncHostHardware($this->vCenter);
        $sync->run();
    }

    public function syncHostSensors()
    {
        $sync = new SyncHostSensors($this->vCenter);
        $sync->run();
    }

    public function syncVmHardware()
    {
        $sync = new SyncVmHardware($this->vCenter);
        $sync->run();
    }

    public function syncVmDiskUsage()
    {
        $sync = new SyncVmDiskUsage($this->vCenter);
        $sync->run();
    }

    public function syncVmDatastoreUsage()
    {
        $sync = new SyncVmDatastoreUsage($this->vCenter);
        $sync->run();
    }

    /**
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\ConfigurationError
     * @throws \Zend_Db_Adapter_Exception
     */
    public function streamEvents()
    {
        $cnt = $this->eventManager->streamToDb();
        if ($cnt < 1000) {
            Logger::debug('Got %d event(s), there might be more', $cnt);
        } else {
            Logger::debug('Got %d events', $cnt);
        }
    }

    protected function closeDbConnection()
    {
        if ($this->connection !== null) {
            $this->connection->getDbAdapter()->closeConnection();
            Logger::debug(
                'Closed database connection for %s',
                $this->getLogName()
            );
        }
    }

    protected function reset()
    {
        $this->isReady = false;
        try {
            Logger::info(
                'Resetting vSphereDB main runner for %s',
                $this->getLogName()
            );
            $this->eventuallyLogout();
            $this->closeDbConnection();
            $this->initialize();
            $this->isReady = true;
        } catch (Exception $e) {
            Logger::error(
                'Failed to reset vSphereDB main runner for %s: %s -> %s',
                $this->getLogName(),
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }

    protected function eventuallyLogout()
    {
        if ($this->vCenter !== null) {
            try {
                $this->vCenter->getApi()->logout();
            } catch (Exception $e) {
                // Well, that's fine.
            }
        }
    }

    protected function getLogName()
    {
        return sprintf('vCenter id=%d', $this->vCenterId);
    }

    protected function runFailSafe($method)
    {
        if (! $this->isReady) {
            return;
        }

        try {
            $method();
        } catch (Exception $e) {
            Logger::error($e);

            Logger::error($e->getTraceAsString());
            $this->reset();
        }
    }

    protected function loop()
    {
        if ($this->loop === null) {
            $this->loop = Loop::create();
        }

        return $this->loop;
    }
}
