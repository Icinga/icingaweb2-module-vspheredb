<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseHtmlElement;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Util\Format;
use dipl\Html\Link;

class DatastoreUsage extends BaseHtmlElement
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

    /** @var string */
    protected $uuid;

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
        $this->uuid        = $datastore->get('uuid');
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
                ['o.uuid', 'o.object_name', 'vdu.committed', 'vdu.uncommitted']
            )->join(
                ['o' => 'object'],
                'o.uuid = vdu.vm_uuid',
                []
            )->where('vdu.datastore_uuid = ?', $this->datastore->get('uuid'))
            ->order('o.object_name');

        foreach ($this->db->fetchAll($query) as $row) {
            $this->addDiskFromDbRow($row);
        }

        return $this;
    }

    public function addDiskFromDbRow($row)
    {
        $info = $this->makeDisk($row);
        $this->addVmDisk($info->title, $info->datastore_percent, $info->vm_uuid);
        return $this;
    }

    public function addFreeDatastoreSpace()
    {
        $title = sprintf('Free space');
        $free = $this->datastore->get('free_space');
        if ($this->uncommitted < $free) {
            $class = 'free';
        } elseif ($this->uncommitted > 2 * $this->capacity) {
            $title = sprintf('Committed space');
            $class = 'free overcommitted-twice';
        } else {
            $title = sprintf('Committed space');
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

    public function addVmDisk($title, $percent, $vmUuid = null, $attributes = [])
    {
        if ($vmUuid) {
            $url = 'vspheredb/vm';
            $urlParams = ['uuid' => bin2hex($vmUuid)];
        } else {
            $url = '#';
            $urlParams = null;
        }

        $percent = round($percent * 1000) / 1000;
        // TODO: still unused
        $this->gotPercent += $percent;
        if ($this->gotPercent > 100) {
            $percent = $percent + 100 - $this->gotPercent;
        }

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

        if ($vmUuid) {
            $alpha = (20 + (crc32(sha1((string) $vmUuid . $this->uuid)) % 60)) / 100;
            $color = sprintf('rgba(70, 128, 255, %.2F);', $alpha);
            $link->getAttributes()->add('style', "background-color: $color");
            $this->diskLinks[$vmUuid] = $link;
        }
        $this->add($link);

        return $this;
    }

    protected function makeDisk($dbRow)
    {
        $size = $dbRow->committed + $dbRow->uncommitted;
        $share = (object) [
            'vm_uuid' => $dbRow->uuid,
            'name'  => $dbRow->object_name,
            'size'  => $size,
            'used'  => $dbRow->committed,
            'used_percent'        => ($dbRow->committed / $size) * 100,
            'datastore_percent'   => ($dbRow->committed / $this->capacity) * 100,
            'uncommitted'         => $dbRow->uncommitted,
            'uncommitted_percent' => $this->uncommitted > 0
                ? ($dbRow->uncommitted / $this->uncommitted) * 100
                : 0,
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
