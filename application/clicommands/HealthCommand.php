<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\DbObject\Vcenter;

/**
 * Health information for a vCenter or ESXi host
 */
class HealthCommand extends CommandBase
{
    public function checkAction()
    {
        Benchmark::measure('Preparing the API');
        $api = $this->api();
        Benchmark::measure('Logged in, ready to fetch');

        try {
            $time = $api->getCurrentTime()->format('U.u');
            $timediff = microtime(true) - (float)$time;
            if (abs($timediff) > 0.1) {
                printf("%0.3fms Time difference detected\n", $timediff * 1000);
            }

            $vcenter = $this->getVcenter($api);
            printf("Connected to a %s\n", $vcenter->getFullName());
            echo $api->getVersionString();
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString();
            echo Benchmark::dump();
        }
    }

    // Hint: this also updates the vCenter
    protected function getVcenter(Api $api)
    {
        $about = $api->getAbout();
        $uuid = $api->getBinaryUuid();
        if (Vcenter::exists($uuid, $this->db())) {
            $vcenter = Vcenter::load($uuid, $this->db());
        } else {
            $vcenter = Vcenter::create([], $this->db());
        }
        $vcenter->setMapped($about);

        if ($vcenter->hasBeenModified()) {
            if ($vcenter->hasBeenLoadedFromDb()) {
                $msg = 'vCenter has been modified';
            } else {
                $msg = 'vCenter has been created';
            }

            $vcenter->store();
            echo "$msg\n";
        }

        return $vcenter;
    }
}
