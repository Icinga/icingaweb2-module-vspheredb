<?php

namespace Icinga\Module\Vsphere\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Vsphere\ManagedObject\Folder;
use Icinga\Module\Vsphere\ManagedObject\FullTraversal;
use Icinga\Module\Vsphere\ManagedObject\HostSystem;
use Icinga\Module\Vsphere\ManagedObject\VirtualMachine;
use Icinga\Module\Vsphere\Util;

/**
 * Fetch information from a vCenter or ESXi host
 *
 * This is mostly for debugging purposes but might also be used for some kind
 * of automation scripts
 */
class FetchCommand extends CommandBase
{
    /**
     * Fetch all available VirtualMachines
     *
     * Mostly for test/debug reasons. Output occurs with default properties
     *
     * USAGE
     *
     * icingacli vsphere fetch virtualmachines \
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
     *   --benchmark              Show benchmark summary
     *   --json                   Dump JSON-encoded
     */
    public function virtualmachinesAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $objects = VirtualMachine::fetchWithDefaults($api);
        Benchmark::measure(sprintf("Got %d VMs", count($objects)));

        if ($this->params->get('lookup-ids')) {
            $api->idLookup()->enrichObjects($objects);
        }
        Benchmark::measure('Mapped properties');
        $api->logout();
        Benchmark::measure('Logged out');
        $objects = Util::createNestedObjects($objects);

        if ($this->params->get('json')) {
            echo json_encode($objects);
        } else {
            print_r($objects);
        }
    }

    /**
     * Fetch all available HostSystems
     *
     * Mostly for test/debug reasons. Output occurs with default properties
     *
     * USAGE
     *
     * icingacli vsphere fetch hostsystems \
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
     *   --benchmark              Show benchmark summary
     *   --json                   Dump JSON-encoded
     */
    public function hostsystemsAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $objects = HostSystem::fetchWithDefaults($api);
        Benchmark::measure(sprintf("Got %d Hosts", count($objects)));
        if ($this->params->get('lookup-ids')) {
            $api->idLookup()->enrichObjects($objects);
        }
        $api->logout();
        Benchmark::measure('Logged out');
        $objects = Util::createNestedObjects($objects);

        if ($this->params->get('json')) {
            echo json_encode($objects);
        } else {
            print_r($objects);
        }
    }

    // Actions below this line might change without pre-announcement, so please
    // do not build serious business logic based on them

    public function folderAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $folder = Folder::fetchWithDefaults($api);
        Benchmark::measure(sprintf("Got %d folder", count($folder)));
        $api->logout();
        Benchmark::measure('Logged out');
        print_r($folder);
    }

    public function treeAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $ids = $api->idLookup();
        $ids->refresh();
        $ids->dump();
        Benchmark::measure('Got them');
        $api->logout();
        Benchmark::measure('Logged out');
    }

    public function fullAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $all = FullTraversal::fetchAll($api);
        Benchmark::measure(sprintf("Got %d objects", count($all)));
        $api->logout();
        Benchmark::measure('Logged out');
        print_r($all);
    }
}
