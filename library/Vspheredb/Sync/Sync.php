<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VmConfig;
use Icinga\Module\Vspheredb\IdLookup;

class Sync
{
    /** @var Api */
    protected $api;

    /** @var Db */
    protected $db;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $dba;

    public function __construct(Api $api, Db $db)
    {
        $this->api = $api;
        $this->db = $db;
        $this->dba = $db->getDbAdapter();
    }

    public function syncAllObjects()
    {
        $api = $this->api;
        $db = $this->db;
        Benchmark::measure('Refreshing objects');
        (new IdLookup($api, $db))->refresh();
        Benchmark::measure('Refreshed objects');
        HostSystem::syncFromApi($api, $db);
        VmConfig::syncFromApi($api, $db);
        Datastore::syncFromApi($api, $db);

        return $this;
    }
}
