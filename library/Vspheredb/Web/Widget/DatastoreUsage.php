<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\BaseHtmlElement;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Util\Format;
use gipfl\IcingaWeb2\Link;

class DatastoreUsage extends BaseHtmlElement
{
    use TranslationHelper;

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

    protected $baseUrl = 'vspheredb/vm';

    /** @var Link[] Array key is the VirtualMachine id */
    protected $diskLinks;

    public function __construct(Datastore $datastore)
    {
        $this->datastore   = $datastore;
        $this->uuid        = $datastore->get('uuid');
        $this->capacity    = (int) $datastore->get('capacity');
        $this->uncommitted = (int) $datastore->get('uncommitted');
        $this->db = $datastore->getDb();
    }

    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;

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
            // ->order('o.object_name');
            ->order('vdu.committed DESC');

        $result = $this->db->fetchAll($query);
        $cnt = 0;
        $shown = 0;
        $others = 0;
        $limit = 10000; // Impossible limit for now.
        foreach ($result as $row) {
            $row->object_name = Anonymizer::anonymizeString($row->object_name);
            $cnt++;
            if ($cnt < $limit) {
                $shown += $row->committed + $row->uncommitted;
            } else {
                $others += $row->committed + $row->uncommitted;
            }
        }

        $cnt = 0;
        foreach ($result as $row) {
            $cnt++;
            $this->addDiskFromDbRow($row);
            if ($cnt === $limit) {
                break;
            }
        }
        // TODO:
        // if ($shown > 0) {
        //    $this->addDiskFromDbRow((object) [
        //        'o.uuid' => null,
        //        'o.object_name' => 'X other VMs',
        //        'vdu.committed' => $diff
        //        'vdu.uncommitted' => 0
        //    ]);
        // }
        gc_collect_cycles();

        return $this;
    }

    public function addDiskFromDbRow($row)
    {
        $info = $this->makeDisk($row);
        if ($info !== null) {
            $this->addVmDisk($info->title, $info->datastore_percent, $info->vm_uuid);
        }

        return $this;
    }

    public function addFreeDatastoreSpace()
    {
        if ($this->capacity === 0) {
            return $this;
        }
        $title = sprintf('Free space');
        $free = $this->datastore->get('free_space');
        if ($this->uncommitted < $free) {
            $class = 'free';
        } elseif ($this->uncommitted > 2 * $this->capacity) {
            $title = sprintf('Committed space');
            $class = 'free overcommitted-twice';
        } else {
            $title = sprintf('Free space');
            $class = 'free overcommitted';
        }

        $percent = ($free / $this->capacity) * 100;
        $unknownPercent = 100 - $percent - $this->gotPercent;
        if ($unknownPercent > 0) {
            $this->addVmDisk(
                $this->translate('Unknown / not used by any visible Virtual Machine'),
                $unknownPercent,
                null,
                ['class' => 'unknown']
            );
        }
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
            $url = $this->baseUrl;
            $urlParams = Util::uuidParams($vmUuid);
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
        if ($size === 0) {
            return null;
        }

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
