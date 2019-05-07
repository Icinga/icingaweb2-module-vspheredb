<?php

namespace Icinga\Module\Vspheredb\Web;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class OverviewTree extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $db;

    protected $typeFilter;

    public function __construct(Db $db, $typeFilter = null)
    {
        $this->db = $db;
        $this->typeFilter = $typeFilter;
    }

    public function renderContent()
    {
        $this->add(
            $this->dumpTree(
                (object) [
                    'object_name' => $this->translate('vSphere'),
                    'object_type' => 'root',
                    'children' => $this->getTree()
                ]
            )
        );

        return parent::renderContent();
    }

    protected function getTree()
    {
        $tree = [];
        $all = [];
        foreach ($this->fetchTree() as $item) {
            if ($this->typeFilter
                && (int) $item->level === 2
                && $item->object_name !== $this->typeFilter) {
                continue;
            }
            $item->children = [];
            $all[$item->uuid] = $item;
            if ($item->parent_uuid === null) {
                $tree[$item->uuid] = $item;
            } else {
                $all[$item->parent_uuid]->children[$item->uuid] = $item;
            }
        }

        return $tree;
    }

    protected function fetchTree()
    {
        $hostCnt = "SELECT COUNT(*) as cnt, parent_uuid"
            . " FROM object WHERE object_type = 'HostSystem'"
            . " GROUP BY parent_uuid";
        $vmCnt = "SELECT COUNT(*) as cnt, parent_uuid"
            . " FROM object WHERE object_type = 'VirtualMachine'"
            . " GROUP BY parent_uuid";
        $dsCnt = "SELECT COUNT(*) as cnt, parent_uuid"
            . " FROM object WHERE object_type = 'Datastore'"
            . " GROUP BY parent_uuid";
        $networkCnt = "SELECT COUNT(*) as cnt, parent_uuid"
            . " FROM object WHERE object_type = 'DistributedVirtualSwitch'"
            . " GROUP BY parent_uuid";
        $main = "SELECT * FROM object"
            . " WHERE object_type NOT IN ('VirtualMachine', 'HostSystem', 'Datastore')";

        $sql = "SELECT f.*, hc.cnt AS cnt_host, vc.cnt AS cnt_vm, dc.cnt AS cnt_ds, nc.cnt AS cnt_network"
             . " FROM ($main) f"
             . " LEFT JOIN ($vmCnt) vc ON vc.parent_uuid = f.uuid"
             . " LEFT JOIN ($hostCnt) hc ON hc.parent_uuid = f.uuid"
             . " LEFT JOIN ($dsCnt) dc ON dc.parent_uuid = f.uuid"
             . " LEFT JOIN ($networkCnt) nc ON dc.parent_uuid = f.uuid"
             . " ORDER BY f.level ASC, f.object_name";

        return $this->db->getDbAdapter()->fetchAll($sql);
    }

    protected function dumpTree($tree, $level = 0)
    {
        $hasChildren = ! empty($tree->children);
        $type = $tree->object_type;
        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->getAttributes()->add('class', 'collapsed');
        }

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        }

        if ($level === 0) {
            $li->add(Html::tag('a', [
                'name'  => $tree->object_name,
                'class' => 'icon-globe'
            ], $tree->object_name));
        } else {
            $count = $tree->cnt_vm + $tree->cnt_host + $tree->cnt_ds;
            if ($count) {
                $label = sprintf('%s (%d)', $tree->object_name, $count);
                // $label = sprintf('%s (%d VMs, %d Hosts)', $tree->object_name, $tree->cnt_vm, $tree->cnt_host);
            } else {
                $label = $tree->object_name;
            }
            $attributes = [
                'class' => [$this->getClassByType($type), $tree->overall_status]
            ];

            if ($count) {
                $li->add(Link::create(
                    $label,
                    $tree->cnt_host > 0
                        ? 'vspheredb/hosts'
                        : ($tree->cnt_ds > 0 ? 'vspheredb/datastores' : 'vspheredb/vms'),
                    array('uuid' => bin2hex($tree->uuid)),
                    $attributes
                ));
            } else {
                $li->add(Html::tag('a', $attributes, $label));
            }
        }

        if ($hasChildren) {
            $li->add(
                $ul = Html::tag('ul')
            );
            foreach ($tree->children as $child) {
                $ul->add($this->dumpTree($child, $level + 1));
            }
        }

        return $li;
    }

    protected function getClassByType($type)
    {
        $typeClasses = [
            'ComputeResource'        => 'cubes',
            'ClusterComputeResource' => 'cubes',
            'Datacenter'             => 'home',
            'DistributedVirtualPortgroup' => 'plug',
            'DistributedVirtualSwitch' => 'sitemap',
            'VmwareDistributedVirtualSwitch' => 'sitemap',
            'Datastore'              => 'database',
            // 'DatastoreHostMount',
            'Folder'                 => 'folder-empty',
            'Network'                => 'arrows-cw',
            'ResourcePool'           => 'chart-pie',
            'StoragePod'             => 'cloud',
            'HostSystem'             => 'host',
            'VirtualApp'             => 'th-thumb-empty',
            'VirtualMachine'         => 'service',
        ];
        return 'icon-' . $typeClasses[$type];
    }
}
