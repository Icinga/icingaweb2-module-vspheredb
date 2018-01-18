<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use Icinga\Module\Vspheredb\DbObject\Vcenter;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Object\VcenterInfoTable;

class VcenterController extends Controller
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('vCenter Overview'));
        $vcenter = Vcenter::loadWithAutoIncId(1, $this->db());
        $this->content()->add(new VcenterInfoTable($vcenter));
        $db = $this->db()->getDbAdapter();
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('object', 'COUNT(*)')
                    ->where('object_type = ?', 'Datacenter')
            ),
            'Datacenters'
        );
        $this->addCountlet(
            $db->fetchOne($db->select()->from('host_system', 'COUNT(*)')),
            'Host Systems'
        );
        $this->addCountlet(
            $db->fetchOne($db->select()->from('datastore', 'COUNT(*)')),
            'Datastores'
        );
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('virtual_machine', 'COUNT(*)')
                    ->where('template = ?', 'n')
            ),
            'Virtual Machines'
        );
        // 4 base VMs are missing! (parent_id = null)
        $this->addCountlet(
            $db->fetchOne(
                $db->select()->from('virtual_machine', 'COUNT(*)')
                    ->where('template = ?', 'y')
            ),
            'VM Templates'
        );
    }

    protected function addCountlet($count, $title)
    {
        $this->content()->add(
            Html::tag('div', [
                'style' => 'width: 20em; background-color: gray; color: white; height: 20em; display: inline-block;'
            ], [
                Html::tag('div', ['style' => 'font-size: 6em; line-height: 2.5em; text-align: center;'], $count),
                Html::tag('div', ['style' => 'font-size: 1.5em; line-height: 1em; text-align: center;'], $title),
            ])
        );
    }
}
