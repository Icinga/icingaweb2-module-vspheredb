<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
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

        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('object', 'COUNT(*)')
                    ->where('object_type = ?', 'Datacenter')
            ),
            'Datacenters',
            'vspheredb/datacenters'
        );
        $this->addCountlet(
            $db->fetchOne($db->select()->from('host_system', 'COUNT(*)')),
            'Host Systems',
            'vspheredb/hosts'
        );
        $this->addCountlet(
            $db->fetchOne($db->select()->from('datastore', 'COUNT(*)')),
            'Datastores',
            'vspheredb/datastores'
        );
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('virtual_machine', 'COUNT(*)')
                    ->where('template = ?', 'n')
            ),
            'Virtual Machines',
            'vspheredb/vms'
        );
        // 4 base VMs are missing! (parent_id = null)
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('virtual_machine', 'COUNT(*)')
                    ->where('template = ?', 'y')
            ),
            'VM Templates',
            'vspheredb/vmtemplates'
        );
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('object', 'COUNT(*)')
                    ->where('object_type IN (?)', [
                        'DistributedVirtualSwitch',
                        'VmwareDistributedVirtualSwitch'
                    ])
            ),
            'Virtual Switches',
            'vspheredb/switches'
        );
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('object', 'COUNT(*)')
                    ->where('object_type = ?', 'ResourcePool')
            ),
            'Resource Pools',
            'vspheredb/resourcepools'
        );
    }

    protected function addCountlet($count, $title, $url)
    {
        $this->add(Link::create([
            Html::tag('span', ['class' => 'number'], $count),
            Html::tag('span', ['class' => 'title'], $title),
        ], $url));
    }
}
