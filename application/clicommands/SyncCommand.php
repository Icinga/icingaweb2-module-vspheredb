<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\VmConfig;
use Icinga\Module\Vspheredb\DbObject\HostSystem;

/**
 * Sync a vCenter or ESXi host
 */
class SyncCommand extends CommandBase
{
    /** @var Api */
    protected $api;

    /** @var VmConfig[] */
    protected $vms;

    /** @var HostSystem[] */
    protected $hosts;

    /**
     * Sync Test
     *
     * Mostly for test/debug reasons. Output occurs with default properties
     *
     * USAGE
     *
     * icingacli vsphere sync test \
     *     --vhost <vcenter> \
     *     --username <username> \
     *     --password <password> \
     *     [options]
     *
     * OPTIONS
     *
     *   --lookup-ids             Replace id-references with their name
     *                            This requires one additional API request
     *   --no-ssl-verify-peer     Accept certificates signed by unknown CA
     *   --no-ssl-verify-host     Accept certificates not matching the host
     *   --use-insecure-http      Use plaintext HTTP requests
     *   --proxy <proxy>          Use the given Proxy (ip, host or host:port)
     *   --proxy-type <type>      HTTP (default) or SOCKS5
     *   --proxy-username <user>  Username for authenticated HTTP proxy
     *   --proxy-password <pass>  Password for authenticated HTTP proxy
     *   --benchmark              Show resource usage summary
     *   --json                   Dump JSON output
     */
    public function testAction()
    {
        $cnt = 0;
        echo $this->screen->clear();
        while (true) {
            $cnt++;
            $this->login();
            $this->syncObjects();
            HostSystem::syncFromApi($this->api(), $this->db());
            VmConfig::syncFromApi($this->api(), $this->db());
            Datastore::syncFromApi($this->api(), $this->db());
            echo $this->screen->clear();
            Benchmark::dump();
            Benchmark::reset();
            if ($cnt > 0) {
                break;
            } else {
                sleep(10);
            }
        }

        $this->logout();
    }

    protected function login()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        $this->api = $api;
        Benchmark::measure('Logged in');
    }

    protected function syncObjects()
    {
        Benchmark::measure('Refreshing objects');
        $this->api->idLookup($this->db())->refresh();
        Benchmark::measure('Refreshed objects');
    }

    protected function logout()
    {
        Benchmark::measure('Trying to log out');
        $this->api->logout();
        Benchmark::measure('Logging out');
    }
}
