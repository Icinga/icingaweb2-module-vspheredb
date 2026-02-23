<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class DatastorePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('Datastore', [
                'summary.maintenanceMode', // "normal"
                'summary.accessible',
                'summary.freeSpace',
                'summary.capacity',
                'summary.uncommitted',
                'summary.multipleHostAccess',
                // 'host',          // DatastoreHostMount[]
                // 'info',          // DataStoreInfo
            ])
        ];
    }
}
