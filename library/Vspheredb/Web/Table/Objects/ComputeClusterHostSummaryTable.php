<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

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
        return ['uuid' => bin2hex($row->uuid)];
    }
}
