<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

class VCenterSummaryTable extends HostSummaryTable
{
    protected $baseUrl = 'vspheredb/vcenter';

    protected $groupBy = 'o.vcenter_uuid';

    protected $nameColumn = 'vcs.host';

    protected function prepareUnGroupedQuery()
    {
        $db = $this->db();

        $servers = $db->select()->from(
            ['svs' => 'vcenter_server'],
            ['srv_id' => 'MIN(svs.id)']
        )->join(
            ['sv' => 'vcenter'],
            'sv.id = svs.vcenter_id',
            []
        )->group('svs.vcenter_id')->having('srv_id = vcs.id');

        return parent::prepareUnGroupedQuery()->join(
            ['vc' => 'vcenter'],
            'vc.instance_uuid = o.vcenter_uuid',
            []
        )->join(
            ['vcs' => 'vcenter_server'],
            'vcs.vcenter_id = vc.id',
            []
        )->where('EXISTS ?', $servers);
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
