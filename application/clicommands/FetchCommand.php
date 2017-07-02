<?php

namespace Icinga\Module\Vsphere\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Vsphere\ManagedObject\Folder;
use Icinga\Module\Vsphere\ManagedObject\FullTraversal;
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
     *   --json         Dump JSON-encoded
     */
    public function virtualmachinesAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        $api->login();
        Benchmark::measure('Logged in, ready to fetch');
        $vms = VirtualMachine::fetchWithDefaults($api);
        Benchmark::measure(sprintf("Got %d VMs", count($vms)));

        if ($this->params->get('lookup-ids')) {
            $ids = $api->idLookup();
            foreach ($vms as $vm) {
                $vm->folder = $ids->getInheritanceNamePathToId($vm->id);
                $vm->parent = $ids->getNameForId($vm->parent);
                $vm->{'runtime.host'} = $ids->getNameForId($vm->{'runtime.host'});
            }
        }
        Benchmark::measure('Mapped properties');
        $api->logout();
        Benchmark::measure('Logged out');
        if ($this->params->get('json')) {
            echo json_encode($vms);
        } else {
            print_r($vms);
        }
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
