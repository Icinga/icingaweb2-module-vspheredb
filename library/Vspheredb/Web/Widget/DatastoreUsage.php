<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Util\Format;
use dipl\Html\Attributes;
use dipl\Html\BaseElement;
use dipl\Html\Link;

class DatastoreUsage extends BaseElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'disk-usage',
        'data-base-target' => '_next'
    ];

    /** @var Datastore */
    protected $datastore;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var int */
    protected $id;

    /** @var int */
    protected $capacity;

    /** @var int */
    protected $uncommitted;

    protected $gotPercent = 0;

    /** @var Link[] Array key is the VirtualMachine id */
    protected $diskLinks;

    public function __construct(Datastore $datastore)
    {
        $this->datastore   = $datastore;
        $this->id          = $datastore->get('id');
        $this->capacity    = $datastore->get('capacity');
        $this->uncommitted = $datastore->get('uncommitted');
        $this->db = $datastore->getDb();
    }

    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function loadAllVmDisks()
    {
        $query = $this->db->select()
            ->from(
                ['vdu' => 'vm_datastore_usage'],
                ['o.id', 'o.object_name', 'vdu.committed', 'vdu.uncommitted']
            )->join(
                ['o' => 'object'],
                'o.id = vdu.vm_id',
                []
            )->where('vdu.datastore_id = ?', $this->datastore->get('id'))
            ->order('o.object_name');

        foreach ($this->db->fetchAll($query) as $row) {
            $this->addDiskFromDbRow($row);
        }

        return $this;
    }

    public function addDiskFromDbRow($row)
    {
        $info = $this->makeDisk($row);
        $this->addVmDisk($info->title, $info->datastore_percent, $info->vm_id);
        return $this;
    }

    public function addFreeDatastoreSpace()
    {
        $title = sprintf('Free space');
        $free = $this->datastore->get('free_space');
        if ($this->uncommitted < $free) {
            $class = 'free';
        } elseif ($this->uncommitted > 2 * $this->capacity) {
            $class = 'free overcommitted-twice';
        } else {
            $class = 'free overcommitted';
        }

        $percent = ($free / $this->capacity) * 100;
        $this->addVmDisk(
            $title,
            $percent,
            null,
            ['class' => $class]
        );

        return $this;
    }

    public function addVmDisk($title, $percent, $vm_id = null, $attributes = [])
    {
        if ($vm_id) {
            $url = 'vspheredb/vm';
            $urlParams = ['id' => $vm_id];
        } else {
            $url = '#';
            $urlParams = null;
        }

        // TODO: still unused
        $this->gotPercent += $percent;

        $link = Link::create(
            '',
            $url,
            $urlParams,
            [
                'style' => sprintf('width: %.3F%%; ', $percent),
                'title' => $title
            ]
        );

        $link->addAttributes($attributes);

        if ($vm_id) {
            $alpha = (20 + (crc32(sha1((string) $vm_id . $this->id)) % 60)) / 100;
            $color = sprintf('rgba(70, 128, 255, %.2F);', $alpha);
            $link->attributes()->add('style', "background-color: $color");
            $this->diskLinks[$vm_id] = $link;
        }
        $this->add($link);

        return $this;
    }

    protected function makeDisk($dbRow)
    {
        $share = (object) [
            'vm_id' => $dbRow->id,
            'name'  => $dbRow->object_name,
            'size'  => $dbRow->committed + $dbRow->uncommitted,
            'used'  => $dbRow->committed,
            'used_percent'        => ($dbRow->committed / ($dbRow->committed + $dbRow->uncommitted)) * 100,
            'datastore_percent'   => ($dbRow->committed / $this->capacity) * 100,
            'uncommitted'         => $dbRow->uncommitted,
            'uncommitted_percent' => ($dbRow->uncommitted / $this->uncommitted) * 100,
            'extra-class' => null,
        ];
        $share->title = sprintf(
            '%s (%.2f%% of %s) used by %s',
            $this->bytes($share->used),
            $share->used_percent,
            $this->bytes($share->size),
            $share->name
        );

        return $share;
    }

    protected function bytes($bytes)
    {
        return Format::bytes($bytes, Format::STANDARD_IEC);
    }
}
