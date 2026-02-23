<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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

if (Module::exists('icingadb')) {
    $this->provideHook('icingadb/HostDetailExtension');
}

if (Module::exists('monitoring')) {
    $this->provideHook('monitoring/DetailviewExtension');
}
