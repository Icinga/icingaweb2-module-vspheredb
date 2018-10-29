<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

class VCenterSummaryTable extends HostSummaryTable
{
    protected $baseUrl = 'vspheredb/resources/clusters';

    protected $groupBy = 'o.vcenter_uuid';

    protected $nameColumn = 'vcs.host';

    protected function prepareUnGroupedQuery()
    {
        return parent::prepareUnGroupedQuery()->join(
            ['vc' => 'vcenter'],
            'vc.instance_uuid = o.vcenter_uuid',
            []
        )->join(
            ['vcs' => 'vcenter_server'],
            'vcs.vcenter_id = vc.id',
            []
        );
    }

    protected function getGroupingTitle()
    {
        return $this->translate('VCenter');
    }

    protected function getFilterParams($row)
    {
        return ['vcenter' => bin2hex($row->uuid)];
    }
}
