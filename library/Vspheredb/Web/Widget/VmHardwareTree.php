<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Util;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class VmHardwareTree extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $tree;

    /** @var VirtualMachine */
    protected $vm;

    protected $devices = [];

    protected $parents = [];

    protected $children = [];

    protected $disks = [];

    protected $nics = [];

    protected $diskPerf;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    /**
     * @return Db
     */
    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function fetchDisks()
    {
        $db = $this->getDb()->getDbAdapter();
        $query = $db->select()
            ->from('vm_disk')
            ->where('vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_key');

        foreach ($db->fetchAll($query) as $disk) {
            $this->disks[$disk->hardware_key] = $disk;
        }
    }

    protected function fetchNics()
    {
        $db = $this->getDb()->getDbAdapter();
        $query = $db->select()
            ->from('vm_network_adapter')
            ->where('vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_key');

        foreach ($db->fetchAll($query) as $nic) {
            $this->nics[$nic->hardware_key] = $nic;
        }
    }

    protected function fetchHardware()
    {
        $this->fetchDisks();
        $this->fetchNics();
        $this->diskPerf = $this->fetchDiskPerf();
        $db = $this->getDb()->getDbAdapter();
        $query = $db->select()
            ->from('vm_hardware')
            ->where('vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_key');

        foreach ($db->fetchAll($query) as $row) {
            $this->devices[$row->hardware_key] = $row;
            if ($row->controller_key === null) {
                $this->parents[$row->hardware_key] = $row;
            } else {
                $this->children[$row->controller_key][$row->unit_number] = $row;
            }
        }
    }

    protected function renderDisk($disk, $device, $controller)
    {
        $lookup = new PathLookup($this->getDb());
        $result = [];
        if ($disk->datastore_uuid !== null) {
            $link = $lookup->linkToObject($disk->datastore_uuid);
            if ($link instanceof Link) {
                $link->getAttributes()->add('class', 'icon-database');
                $caption = (string) current($link->getContent());
                if ($disk->file_name) {
                    $fileName = $disk->file_name;
                    if (strlen($fileName) > 50) {
                        $fileName = substr($fileName, 0, 40) . '...' . substr($fileName, -10);
                    }
                    str_replace('%', '%%', $fileName);
                    $fileName = preg_replace('/\[' . preg_quote($caption, '/') . '\] /', '%s: ', $fileName);
                    $result[] = Html::tag('span', ['title' => $disk->file_name], Html::sprintf($fileName, $link));
                } else {
                    $result[] = $link;
                }
            }
        }
        $scsi = sprintf('scsi%s:%s', $controller->bus_number, $device->unit_number);
        $result[] = " ($scsi)";

        // TODO: show booleans split, write_through and thin_provisioned
        //       Also show disk_mode. What about disk_uuid?
        if ($disk->capacity !== null) {
            $result[] = ' ';
            $result[] = Format::bytes($disk->capacity);
        }

        if (false && array_key_exists($scsi, $this->diskPerf)) {
            $result[] = new CompactInOutSparkline(
                $this->diskPerf[$scsi][171],
                $this->diskPerf[$scsi][172]
            );
        }

        return $result;
    }

    protected function renderNic($nic, $device, $controller)
    {
        $desc = $device->label;

        $parts[] = $nic->mac_address;
        if ($device->summary !== $device->label) {
             // $parts[] = $device->summary;
        }

        $desc = $desc . ': ' . implode(', ', $parts);

        $result = Link::create($desc, '#', null, [
            'class' => 'icon-sitemap',
        ]);

        if ($nic->portgroup_uuid === null) {
            return $result;
        } else {
            return [$result, $this->linkToPortGroup($nic->portgroup_uuid)];
        }
    }

    protected function linkToPortGroup($uuid)
    {
        $db = $this->getDb()->getDbAdapter();
        $info = $db->fetchRow(
            $db->select()->from(
                ['o' => 'object'],
                [
                    'uuid'        => 'o.uuid',
                    'object_name' => 'o.object_name',
                    'cnt_nics'    => 'COUNT(*)',
                ]
            )->join(
                ['vna' => 'vm_network_adapter'],
                'vna.portgroup_uuid = o.uuid',
                []
            )->where(
                'o.uuid = ?',
                $uuid
            )->group('o.uuid')
        );

        if (false === $info) {
            return sprintf('Port group %s not found', Util::uuidToHex($uuid));
        }

        return Link::create(
            sprintf('%s (%d NICs)', $info->object_name, $info->cnt_nics),
            'vspheredb/portgroup',
            ['uuid' => bin2hex($info->uuid)]
        );
    }

    protected function fetchDiskPerf()
    {
        $db = $this->getDb()->getDbAdapter();

        $values = '(' . implode(" || ',' || ", [
            "COALESCE(value_minus4, '0')",
            "COALESCE(value_minus3, '0')",
            "COALESCE(value_minus2, '0')",
            "COALESCE(value_minus1, '0')",
            'value_last',
        ]) . ')';

        $query = $db->select()->from('counter_300x5', [
            // 'name' => 'object_uuid',
            'instance',
            'counter_key',
            'value' => $values,
        ])->where('object_uuid = ?', $this->vm->get('uuid'))
        ->where('counter_key IN (?)', [171, 172]);

        $rows = $db->fetchAll($query);
        $result = [];
        foreach ($rows as $row) {
            $result[$row->instance][$row->counter_key] = $row->value;
        }

        return $result;
    }


    public function assemble()
    {
        $this->fetchHardware();
        $this->add($this->renderNodes($this->parents));
    }

    protected function renderNodes($nodes, $level = 0)
    {
        $result = [];
        foreach ($nodes as $child) {
            $result[] = $this->renderNode($child, $level + 1);
        }

        if ($level === 0) {
            return $result;
        } else {
            return Html::tag('ul', null, $result);
        }
    }

    protected function renderNode($device, $level = 0)
    {
        $key = $device->hardware_key;
        $desc = $device->label;
        if ($device->summary !== $device->label) {
            $desc .= ': ' . $device->summary;
        }
        $hasChildren = array_key_exists($key, $this->children);
        $isDisk = array_key_exists($key, $this->disks);

        // TODO: get serious:
        // $isNic = array_key_exists($key, $this->nics
        $isNic = strpos($desc, 'Network') === 0;

        if ($isDisk) {
            $class = 'icon-database';
        } elseif ($isNic) {
            $class = 'icon-sitemap';
        } else {
            $class = 'icon-doc-text';
        }

        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->getAttributes()->add('class', 'collapsed');
        }

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        }

        if ($isDisk) {
            $li->add($this->renderDisk($this->disks[$key], $device, $this->devices[$device->controller_key]));
        } elseif ($isNic) {
            if (array_key_exists($key, $this->nics)) {
                $li->add($this->renderNic($this->nics[$key], $device, $this->devices[$device->controller_key]));
            } else {
                $li->add(Link::create($desc, '#', null, ['class' => $class, 'title' => 'No more details available']));
            }
        } else {
            $li->add(Link::create($desc, '#', null, ['class' => $class]));
        }

        if ($hasChildren) {
            $li->add($this->renderNodes($this->children[$key], $level + 1));
        }

        return $li;
    }
}
