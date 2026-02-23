<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
use Icinga\Web\Url;

if (Icinga::app()->isCli()) {
    throw new IcingaException(
        "Missing dependencies, please check "
    );
} else {
    $request = Icinga::app()->getRequest();
    $path = $request->getPathInfo();
    if (! preg_match('#^/vspheredb#', $path)) {
        return;
    }
    if (preg_match('#^/vspheredb/phperror/dependencies#', $path)) {
        return;
    }

    header('Location: ' . Url::fromPath('vspheredb/phperror/dependencies'));
    exit;
}
