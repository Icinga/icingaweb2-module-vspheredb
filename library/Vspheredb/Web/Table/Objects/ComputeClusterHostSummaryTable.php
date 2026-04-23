<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Util;

class ComputeClusterHostSummaryTable extends HostSummaryTable
{
    protected $baseUrl = 'vspheredb/compute-cluster';

    protected $baseUrlHosts = 'vspheredb/compute-cluster/hosts';

    protected $groupBy = 'o.uuid';

    protected $nameColumn = 'o.object_name';

    protected function getGroupingTitle()
    {
        return $this->translate('Compute Cluster');
    }

    protected function getFilterParams($row)
    {
        return Util::uuidParams($row->uuid);
    }
}
