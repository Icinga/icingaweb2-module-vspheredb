<?php

namespace Icinga\Module\Vsphere\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Vsphere\ManagedObject\Folder;
use Icinga\Module\Vsphere\ManagedObject\VirtualMachine;

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
     *   --benchmark    Show benchmark summary
     */
    public function virtualmachinesAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $vms = VirtualMachine::fetchWithDefaults($api);
        Benchmark::measure(sprintf("Got %d VMs", count($vms)));
        $api->logout();
        Benchmark::measure('Logged out');
        print_r($vms);
    }

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
}
