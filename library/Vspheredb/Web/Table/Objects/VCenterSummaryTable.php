<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Icon;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\VCenterConnectionStatusIcon;
use ipl\Html\Html;

class VCenterSummaryTable extends HostSummaryTable
{
    protected $baseUrl = 'vspheredb/vcenter';

    protected $groupBy = 'o.vcenter_uuid';

    protected $nameColumn = 'vc.name';

    protected $connections;

    /**
     * @param mixed $connections
     * @return VCenterSummaryTable
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
        return $this;
    }

    protected function getExtraIcons($row)
    {
        if ($this->connections === null) {
            return null;
        }

        $icons = Html::tag('span', [
            'style' => 'float: right'
        ]);
        $vcenterId = $row->vcenter_id;
        if (isset($this->connections[$vcenterId])) {
            foreach ($this->connections[$vcenterId] as $connection) {
                $icons->add(VCenterConnectionStatusIcon::create($connection->state, $connection->server));
            }
        } else {
            $icons->add(Icon::create('warning-empty', [
                'class' => 'yellow',
                'title' => $this->translate('There is no configured server for this vCenter')
            ]));
        }

        return $icons;
    }

    public function getDefaultColumnNames()
    {
        return \array_merge(parent::getDefaultColumnNames(), ['datastore_usage']);
    }

    protected function initialize()
    {
        $this->setAttribute('data-base-target', '_self');
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
            })->setSortExpression('(ds.ds_capacity - ds.ds_free_space) / ds.ds_capacity'),
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
        $ds = $this->db()->select()->from(
            ['ds' => 'datastore'],
            [
                'vcenter_uuid'   => 'ds.vcenter_uuid',
                'ds_capacity'    => 'SUM(ds.capacity)',
                'ds_free_space'  => 'SUM(ds.free_space)',
                'ds_uncommitted' => 'SUM(ds.uncommitted)',
            ]
        )->group('ds.vcenter_uuid');

        return parent::prepareUnGroupedQuery()->join(
            ['vc' => 'vcenter'],
            'vc.instance_uuid = o.vcenter_uuid',
            ['vcenter_id' => 'vc.id']
        )->joinLeft(
            ['ds' => $ds],
            'vc.instance_uuid = ds.vcenter_uuid',
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
