<?php

use Icinga\Application\Modules\Module;
use Icinga\Module\Vspheredb\Application\DependencyChecker;
use Icinga\Module\Vspheredb\ProvidedHook\Director\DataTypeMonitoringRule;
use Icinga\Module\Vspheredb\ProvidedHook\Vspheredb\PerfDataConsumerInfluxDb;

/** @var $this \Icinga\Application\Modules\Module */
$checker = new DependencyChecker($this->app);
if (! $checker->satisfiesDependencies($this)) {
    include __DIR__ . '/run-missingdeps.php';
    return;
}

$this->provideHook('director/ImportSource');
$this->provideHook('director/DataType', DataTypeMonitoringRule::class);
$this->provideHook('vspheredb/PerfDataConsumer', PerfDataConsumerInfluxDb::class);



if (Module::exists('monitoring')) {
    $this->provideHook('monitoring/DetailviewExtension');
}
if (Module::exists('icingadb')) {
    $this->provideHook('icingadb/HostDetailExtension');
}
