<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Vspheredb;

use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Module\Vspheredb\Web\Form\ChooseInfluxDatabaseForm;
use Icinga\Module\Vspheredb\Web\Form\InfluxDbConnectionForm;

class PerfDataConsumerInfluxDb extends PerfDataConsumerHook
{
    public static function getName()
    {
        return 'InfluxDB';
    }

    public function getConfigurationForm(RemoteClient $client)
    {
        return new InfluxDbConnectionForm($this->loop, $client);
    }

    public function getSubscriptionForm(RemoteClient $client)
    {
        return new ChooseInfluxDatabaseForm($this->loop, $client, $this);
    }
}
