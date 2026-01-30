<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Util;

class ComputeClusterHostSummaryTable extends HostSummaryTable
{
    protected ?string $baseUrl = 'vspheredb/compute-cluster';

    protected string $baseUrlHosts = 'vspheredb/compute-cluster/hosts';

    protected ?string $groupBy = 'o.uuid';

    protected ?string $nameColumn = 'o.object_name';

    protected function getGroupingTitle(): string
    {
        return $this->translate('Compute Cluster');
    }

    protected function getFilterParams(object $row): array
    {
        return Util::uuidParams($row->uuid);
    }
}
