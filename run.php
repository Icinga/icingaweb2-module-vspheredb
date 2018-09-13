<?php

use Icinga\Application\Icinga;
/** @var $this \Icinga\Application\Modules\Module */
// Disabled for now
// $this->provideHook('director/ImportSource');

$vendorLibDir = __DIR__ . '/library/vendor';
Icinga::app()->getLoader()->registerNamespace(
    'gipfl\\Calendar',
    "$vendorLibDir/gipfl/Calendar"
);
