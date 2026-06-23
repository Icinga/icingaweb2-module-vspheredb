<?php

use Icinga\Application\Modules\Module;
use Icinga\Module\Vspheredb\ProvidedHook\Director\DataTypeMonitoringRule;
use Icinga\Module\Vspheredb\ProvidedHook\Vspheredb\PerfDataConsumerInfluxDb;

require_once __DIR__ . '/library/compat/Boolean.php';

/** @var $this \Icinga\Application\Modules\Module */
$this->provideHook('director/ImportSource');
$this->provideHook('director/DataType', DataTypeMonitoringRule::class);
$this->provideHook('vspheredb/PerfDataConsumer', PerfDataConsumerInfluxDb::class);

if (Module::exists('icingadb')) {
    $this->provideHook('icingadb/HostDetailExtension');
}

if (Module::exists('monitoring')) {
    $this->provideHook('monitoring/DetailviewExtension');
}
