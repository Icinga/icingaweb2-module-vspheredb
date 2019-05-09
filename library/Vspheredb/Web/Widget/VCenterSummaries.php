<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class VCenterSummaries extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'vcenter-summaries',
        'data-base-target' => '_next'
    ];

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    protected function assemble()
    {
        $db = $this->vCenter->getDb();
        $vCenterUuid = $this->vCenter->getUuid();

        $columns = [
            'total'  => 'COUNT(*)',
            'red'    => "SUM(CASE WHEN o.overall_status = 'red' THEN 1 ELSE 0 END)",
            'yellow' => "SUM(CASE WHEN o.overall_status = 'yellow' THEN 1 ELSE 0 END)",
            'green'  => "SUM(CASE WHEN o.overall_status = 'green' THEN 1 ELSE 0 END)",
            'gray'   => "SUM(CASE WHEN o.overall_status = 'gray' THEN 1 ELSE 0 END)",
        ];

        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'Datacenter')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Datacenters',
            'vspheredb/datacenters'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'HostSystem')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Host Systems',
            'vspheredb/hosts'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'Datastore')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Datastores',
            'vspheredb/datastores'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->join(['vm' => 'virtual_machine'], 'vm.uuid = o.uuid', [])
                    ->where('vm.template = ?', 'n')
                    ->where('vm.vcenter_uuid = ?', $vCenterUuid)),
            'Virtual Machines',
            'vspheredb/vms'
        );
        // 4 base VMs are missing! (parent_id = null)
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->join(['vm' => 'virtual_machine'], 'vm.uuid = o.uuid', [])
                    ->where('vm.template = ?', 'y')
                    ->where('vm.vcenter_uuid = ?', $vCenterUuid)
            ),
            'VM Templates',
            'vspheredb/vmtemplates'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'Network')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Networks',
            'vspheredb/networks'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type IN (?)', [
                        'DistributedVirtualSwitch',
                        'VmwareDistributedVirtualSwitch'
                    ])
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Virtual Switches',
            'vspheredb/switches'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'DistributedVirtualPortgroup')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Distributed Portgroups',
            'vspheredb/portgroups'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'ComputeResource')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Compute Resources',
            'vspheredb/resources/clusters'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'ClusterComputeResource')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Cluster Compute Resources',
            'vspheredb/compute-cluster'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'StoragePod')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Storage Pods',
            'vspheredb/storagepods'
        );
        $this->addCountlet(
            $db->fetchRow(
                $db->select()->from(['o' => 'object'], $columns)
                    ->where('object_type = ?', 'ResourcePool')
                    ->where('vcenter_uuid = ?', $vCenterUuid)
            ),
            'Resource Pools',
            'vspheredb/resourcepools'
        );
    }

    protected function getWorstState($counters)
    {
        foreach (['red', 'yellow', 'gray', 'green'] as $color) {
            if ($counters->$color > 0) {
                return $color;
            }
        }

        // Will not be reached
        return 'gray';
    }

    protected function addCountlet($counters, $title, $url)
    {
        if ((int) $counters->total === 0) {
            return;
        }
        $url = Url::fromPath($url)->with('uuid', bin2hex($this->vCenter->getUuid()));
        $state = $this->getWorstState($counters);
        $link = Link::create([
            Html::tag('span', ['class' => 'number'], $counters->total),
            Html::tag('span', ['class' => 'title'], $title),
        ], $url, null, ['class' => $state]);

        foreach (['red', 'yellow', 'gray'] as $color) {
            continue;
            if ($counters->$color > 0) {
                $link->add(Link::create(
                    $counters->$color,
                    $url->with('overall_status', $color),
                    null,
                    ['class' => $color]
                ));
            }
        }
        $this->add($link);
    }
}
