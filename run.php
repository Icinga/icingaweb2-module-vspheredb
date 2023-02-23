<?php

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
$this->provideHook('monitoring/DetailviewExtension');
