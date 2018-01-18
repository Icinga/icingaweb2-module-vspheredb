<?php

namespace Icinga\Module\Vspheredb\Web;

use Icinga\Module\Vspheredb\Db;
use dipl\Html\BaseElement;
use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;

class OverviewTree extends BaseElement
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
            $all[$item->id] = $item;
            if ($item->parent_id === null) {
                $tree[$item->id] = $item;
            } else {
                $all[$item->parent_id]->children[$item->id] = $item;
            }
        }

        return $tree;
    }

    protected function fetchTree()
    {
        $hostCnt = "SELECT COUNT(*) as cnt, parent_id"
            . " FROM object WHERE object_type = 'HostSystem'"
            . " GROUP BY parent_id";
        $vmCnt = "SELECT COUNT(*) as cnt, parent_id"
            . " FROM object WHERE object_type = 'VirtualMachine'"
            . " GROUP BY parent_id";
        $dsCnt = "SELECT COUNT(*) as cnt, parent_id"
            . " FROM object WHERE object_type = 'DataStore'"
            . " GROUP BY parent_id";
        $main = "SELECT * FROM object"
            . " WHERE object_type NOT IN ('VirtualMachine', 'HostSystem', 'Datastore')";

        $sql = "SELECT f.*, hc.cnt AS cnt_host, vc.cnt AS cnt_vm, dc.cnt AS cnt_ds"
             . " FROM ($main) f"
             . " LEFT JOIN ($vmCnt) vc ON vc.parent_id = f.id"
             . " LEFT JOIN ($hostCnt) hc ON hc.parent_id = f.id"
             . " LEFT JOIN ($dsCnt) dc ON dc.parent_id = f.id"
             . " ORDER BY f.level ASC, f.object_name";

        return $this->db->getDbAdapter()->fetchAll($sql);
    }

    protected function dumpTree($tree, $level = 0)
    {
        $hasChildren = ! empty($tree->children);
        $type = $tree->object_type;
        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->attributes()->add('class', 'collapsed');
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
                    array('id' => $tree->id),
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
            'Datacenter'             => 'sitemap',
            'Datastore'              => 'database',
            // 'DatastoreHostMount',
            'Folder'                 => 'folder-empty',
            'ResourcePool'           => 'chart-pie',
            'StoragePod'             => 'cloud',
            'HostSystem'             => 'host',
            'VirtualMachine'         => 'service',
        ];
        return 'icon-' . $typeClasses[$type];
    }
}
