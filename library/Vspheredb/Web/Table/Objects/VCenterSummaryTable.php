<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;

class VCenterSummaryTable extends HostSummaryTable
{
    protected $baseUrl = 'vspheredb/vcenter';

    protected $groupBy = 'o.vcenter_uuid';

    protected $nameColumn = 'vcs.host';

    public function getDefaultColumnNames()
    {
        return \array_merge(parent::getDefaultColumnNames(), ['datastore_usage']);
    }

    protected function initialize()
    {
        parent::initialize();
        $this->addAvailableColumns([
            $this->createColumn('datastore_usage', $this->translate('Storage'), [
                'ds_capacity'   => 'ds.ds_capacity',
                'ds_free_space' => 'ds.ds_free_space',
            ])->setRenderer(function ($row) {
                return new MemoryUsage(
                    ($row->ds_capacity - $row->ds_free_space) / 1000000,
                    $row->ds_capacity / 1000000
                );
            }),
            $this->createColumn('vcenter_software', $this->translate('Software'), [
                'software_name' => 'vc.api_name',
                'software_version' => 'vc.version',
            ])->setRenderer(function ($row) {
                // VMware ESXi -> ESXi
                return \sprintf(
                    '%s (%s)',
                    \preg_replace('/^VMware /', '', $row->software_name),
                    $row->software_version
                );
            }),
        ]);
    }

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

        $ds = $db->select()->from(
            ['ds' => 'datastore'],
            [
                'vcenter_uuid'            => 'ds.vcenter_uuid',
                'ds_capacity'             => 'SUM(ds.capacity)',
                'ds_free_space'           => 'SUM(ds.free_space)',
                'ds_uncommitted'          => 'SUM(ds.uncommitted)',
            ]
        )->group('ds.vcenter_uuid');

        return parent::prepareUnGroupedQuery()->join(
            ['vc' => 'vcenter'],
            'vc.instance_uuid = o.vcenter_uuid',
            []
        )->join(
            ['vcs' => 'vcenter_server'],
            'vcs.vcenter_id = vc.id',
            []
        )->joinLeft(
            ['ds' => $ds],
            'vc.instance_uuid = ds.vcenter_uuid',
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
