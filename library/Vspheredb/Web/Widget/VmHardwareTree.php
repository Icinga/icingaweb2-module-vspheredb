<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db\DbConnection;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Util;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use Ramsey\Uuid\Uuid;

class VmHardwareTree extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next'
    ];

    protected $tree;

    /** @var VirtualMachine */
    protected VirtualMachine $vm;

    protected array $devices = [];

    protected array $parents = [];

    protected array $children = [];

    protected array $disks = [];

    protected array $nics = [];

    protected ?array $diskPerf = null;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    /**
     * @return DbConnection
     */
    protected function getDb(): DbConnection
    {
        return $this->vm->getConnection();
    }

    protected function fetchDisks(): void
    {
        $connection = $this->getDb();
        $db = $connection->getDbAdapter();
        $query = $db->select()
            ->from('vm_disk')
            ->where('vm_uuid = ?', $connection->quoteBinary($this->vm->get('uuid')))
            ->order('hardware_key');

        /** @var object{hardware_key: int} $disk */
        foreach ($db->fetchAll($query) as $disk) {
            $this->disks[$disk->hardware_key] = $disk;
        }
    }

    protected function fetchNics(): void
    {
        $connection = $this->getDb();
        $db = $connection->getDbAdapter();
        $query = $db->select()
            ->from('vm_network_adapter')
            ->where('vm_uuid = ?', $connection->quoteBinary($this->vm->get('uuid')))
            ->order('hardware_key');

        /** @var object{hardware_key: int} $nic */
        foreach ($db->fetchAll($query) as $nic) {
            $this->nics[$nic->hardware_key] = $nic;
        }
    }

    protected function fetchHardware(): void
    {
        $this->fetchDisks();
        $this->fetchNics();
        $this->diskPerf = $this->fetchDiskPerf();
        $connection = $this->getDb();
        $db = $connection->getDbAdapter();
        $query = $db->select()
            ->from('vm_hardware')
            ->where('vm_uuid = ?', $connection->quoteBinary($this->vm->get('uuid')))
            ->order('hardware_key');

        /** @var object{hardware_key: int, controller_key: ?int, unit_number: ?int} $row */
        foreach ($db->fetchAll($query) as $row) {
            $this->devices[$row->hardware_key] = $row;
            if ($row->controller_key === null) {
                $this->parents[$row->hardware_key] = $row;
            } else {
                $this->children[$row->controller_key][$row->unit_number ?? ''] = $row;
            }
        }
    }

    protected function renderDisk(object $disk, object $device, object $controller): array
    {
        $lookup = new PathLookup($this->getDb()->getDbAdapter());
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

//        if (false && array_key_exists($scsi, $this->diskPerf)) {
//            $result[] = new CompactInOutSparkline(
//                $this->diskPerf[$scsi][171],
//                $this->diskPerf[$scsi][172]
//            );
//        }

        return $result;
    }

    protected function renderNic(object $nic, object $device, object $controller): Link|array
    {
        $parts[] = $nic->mac_address;
//        if ($device->summary !== $device->label) {
//             $parts[] = $device->summary;
//        }

        $result = Link::create(
            $device->label . ': ' . implode(', ', $parts),
            '#',
            null,
            ['class' => 'icon-sitemap']
        );

        if ($nic->portgroup_uuid === null) {
            return $result;
        } else {
            return [$result, $this->linkToPortGroup($nic->portgroup_uuid)];
        }
    }

    protected function linkToPortGroup($uuid): string
    {
        $connection = $this->getDb();
        $db = $connection->getDbAdapter();
        $info = $db->fetchRow(
            $db->select()
                ->from(['o' => 'object'], [
                    'uuid'        => 'o.uuid',
                    'object_name' => 'o.object_name',
                    'cnt_nics'    => 'COUNT(*)'
                ])
                ->join(['vna' => 'vm_network_adapter'], 'vna.portgroup_uuid = o.uuid', [])
                ->where('o.uuid = ?', $connection->quoteBinary($uuid))
                ->group('o.uuid')
        );

        if (false === $info) {
            return sprintf('Port group %s not found', Util::niceUuid($uuid));
        }

        return sprintf('%s (%d NICs)', $info->object_name, $info->cnt_nics);

        // TODO:
//        return Link::create(
//            sprintf('%s (%d NICs)', $info->object_name, $info->cnt_nics),
//            'vspheredb/portgroup',
//            Util::uuidParams($info->uuid)
//        );
    }

    protected function fetchDiskPerf(): array
    {
        $connection = $this->getDb();
        $db = $connection->getDbAdapter();

        $values = '(' . implode(" || ',' || ", [
            "COALESCE(value_minus4, '0')",
            "COALESCE(value_minus3, '0')",
            "COALESCE(value_minus2, '0')",
            "COALESCE(value_minus1, '0')",
            'value_last'
        ]) . ')';

        $query = $db->select()
            ->from('counter_300x5', [
                // 'name' => 'object_uuid',
                'instance',
                'counter_key',
                'value' => $values
            ])
            ->where('object_uuid = ?', $connection->quoteBinary($this->vm->get('uuid')))
            ->where('counter_key IN (?)', [171, 172]);

        $rows = $db->fetchAll($query);
        $result = [];
        /** @var object{counter_key: int, instance: string, value: string} $row */
        foreach ($rows as $row) {
            $result[$row->instance][$row->counter_key] = $row->value;
        }

        return $result;
    }


    public function assemble(): void
    {
        $this->fetchHardware();
        $this->add($this->renderNodes($this->parents));
    }

    protected function renderNodes(array $nodes, int $level = 0): HtmlElement|array
    {
        $result = [];
        foreach ($nodes as $child) {
            $result[] = $this->renderNode($child, $level + 1);
        }

        if ($level === 0) {
            return $result;
        }

        return Html::tag('ul', null, $result);
    }

    protected function renderNode(object $device, int $level = 0): HtmlElement
    {
        /** @var int $key */
        $key = $device->hardware_key;
        $desc = $device->label;
        if ($device->summary !== $device->label) {
            $desc .= ': ' . $device->summary;
        }
        $hasChildren = array_key_exists($key, $this->children);
        $isDisk = array_key_exists($key, $this->disks);

        // TODO: get serious:
        // $isNic = array_key_exists($key, $this->nics
        $isNic = str_starts_with($desc, 'Network');

        if ($isDisk) {
            $class = 'icon-database';
        } elseif ($isNic) {
            $class = 'icon-sitemap';
        } else {
            $class = 'icon-doc-text';
        }

        $li = Html::tag('li');

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        } else {
            $li->getAttributes()->add('class', 'collapsed');
        }

        /** @var int|string $controllerKey */
        $controllerKey = $device->controller_key ?? '';
        if ($isDisk) {
            $li->add($this->renderDisk($this->disks[$key], $device, $this->devices[$controllerKey]));
        } elseif ($isNic) {
            if (array_key_exists($key, $this->nics)) {
                $li->add($this->renderNic($this->nics[$key], $device, $this->devices[$controllerKey]));
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
