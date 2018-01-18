<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseElement;
use dipl\Html\Html;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\DbObject\Vcenter;

class VCenterSummaries extends BaseElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'vcenter-summaries',
        'data-base-target' => '_next'
    ];

    /** @var Vcenter */
    protected $vCenter;

    public function __construct(Vcenter $vCenter)
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
    }

    protected function addCountlet($count, $title, $url)
    {
        $this->add(Link::create([
            Html::tag('span', ['class' => 'number'], $count),
            Html::tag('span', ['class' => 'title'], $title),
        ], $url));
    }
}
