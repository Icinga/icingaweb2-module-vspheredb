<?php

namespace Icinga\Module\Vspheredb\Web;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Auth\RestrictionHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class OverviewTree extends BaseHtmlElement
{
    use TranslationHelper;

    /** @var Db */
    protected $db;

    /** @var RestrictionHelper */
    protected $restrictionHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $typeFilter;

    public function __construct(Db $db, RestrictionHelper $restrictionHelper, $typeFilter = null)
    {
        $this->db = $db;
        $this->typeFilter = $typeFilter;
        $this->restrictionHelper = $restrictionHelper;
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
            if (
                $this->typeFilter
                && (string) $item->parent_object_type === 'Datacenter'
                && $item->object_name !== $this->typeFilter
            ) {
                // continue; // see #260
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
        $db = $this->db->getDbAdapter();
        $hostCnt = $db->select()->from('object', [
            'cnt'         => 'COUNT(*)',
            'parent_uuid' => 'parent_uuid'
        ])->where('object_type = ?', 'HostSystem')->group('parent_uuid');
        $vmCnt = $db->select()->from('object', [
            'cnt'         => 'COUNT(*)',
            'parent_uuid' => 'parent_uuid'
        ])->where('object_type = ?', 'VirtualMachine')->group('parent_uuid');
        $dsCnt = $db->select()->from('object', [
            'cnt'         => 'COUNT(*)',
            'parent_uuid' => 'parent_uuid'
        ])->where('object_type = ?', 'Datastore')->group('parent_uuid');
        $networkCnt = $db->select()->from('object', [
            'cnt'         => 'COUNT(*)',
            'parent_uuid' => 'parent_uuid'
        ])->where('object_type = ?', 'DistributedVirtualSwitch')->group('parent_uuid');

        $main = $db->select()
            ->from(['o' => 'object'], [
                'o.*',
                'parent_object_type' => 'po.object_type',
            ])
            ->joinLeft(['po' => 'object'], 'po.uuid = o.parent_uuid', [])
            ->where(' o.object_type NOT IN (?)', [
                'VirtualMachine',
                'HostSystem',
                'Datastore'
            ]);
        $this->restrictionHelper->filterQuery($hostCnt);
        $this->restrictionHelper->filterQuery($vmCnt);
        $this->restrictionHelper->filterQuery($dsCnt);
        $this->restrictionHelper->filterQuery($networkCnt);
        $this->restrictionHelper->filterQuery($main, 'o.vcenter_uuid');
        $query = $db->select()
            ->from(['f' => $main], [
                'f.*',
                'cnt_host'    => 'hc.cnt',
                'cnt_vm'      => 'vc.cnt',
                'cnt_ds'      => 'dc.cnt',
                'cnt_network' => 'nc.cnt',
            ])
            ->joinLeft(['vc' => $vmCnt], 'vc.parent_uuid = f.uuid', [])
            ->joinLeft(['hc' => $hostCnt], 'hc.parent_uuid = f.uuid', [])
            ->joinLeft(['dc' => $dsCnt], 'dc.parent_uuid = f.uuid', [])
            ->joinLeft(['nc' => $networkCnt], 'nc.parent_uuid = f.uuid', [])
            ->order('f.level ASC')
            ->order('f.object_name');

        return $this->db->getDbAdapter()->fetchAll($query);
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
                    Util::uuidParams($tree->uuid),
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
        if (isset($typeClasses[$type])) {
            return 'icon-' . $typeClasses[$type];
        } else {
            return 'icon-attention-alt';
        }
    }
}
