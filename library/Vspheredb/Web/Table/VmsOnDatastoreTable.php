<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use ipl\Html\HtmlElement;
use Zend_Db_Select;

class VmsOnDatastoreTable extends ZfQueryBasedTable
{
    protected $searchColumns = ['object_name'];

    /** @var ?Datastore */
    protected ?Datastore $datastore = null;

    /** @var ?string */
    protected ?string $uuid = null;

    /** @var ?int */
    protected ?int $capacity = null;

    /** @var ?int */
    protected ?int $uncommitted = null;

    public static function create(Datastore $datastore): VmsOnDatastoreTable
    {
        return (new static($datastore->getConnection()))->setDatastore($datastore);
    }

    protected function setDatastore(Datastore $datastore): static
    {
        $this->datastore   = $datastore;
        $this->uuid        = $datastore->get('uuid');
        $this->capacity    = $datastore->get('capacity');
        $this->uncommitted = $datastore->get('uncommitted');

        return $this;
    }

    public function getColumnsToBeRendered(): array
    {
        return [
            $this->translate('Virtual Machine'),
            $this->translate('Size'),
            $this->translate('Usage'),
            $this->translate('On Datastore'),
        ];
    }

    public function renderRow($row): HtmlElement
    {
        $row->object_name = Anonymizer::anonymizeString($row->object_name);
        $size = $row->committed + $row->uncommitted;
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            Util::uuidParams($row->uuid),
            ['title' => sprintf($this->translate('Virtual Machine: %s'), $row->object_name)]
        );

        $usage = (new DatastoreUsage($this->datastore))
            ->setCapacity($size)
            ->addDiskFromDbRow($row);
        $usage->getAttributes()->add('class', 'compact');
        $dsUsage = (new DatastoreUsage($this->datastore))
            ->addDiskFromDbRow($row);
        $dsUsage->getAttributes()->add('class', 'compact');

        $tr = $this::tr([
            $this::td($caption, ['class' => 'vm-on-datastore-caption']),
            $this::td(Format::bytes($size), ['class' => 'vm-on-datastore-size']),
            $this::td($usage, ['class' => 'vm-on-datastore-usage']),
            $this::td($dsUsage, ['class' => 'vm-on-datastore-on-datastore'])
        ]);
        if (time() - 3600 > ($row->ts_updated / 1000)) {
            $caption->add([
                ' ',
                Icon::create('spinner', [
                    'title' => sprintf(
                        $this->translate('Information is outdated, last update took place %s'),
                        DateFormatter::timeAgo($row->ts_updated / 1000)
                    )
                ])
            ]);
            $tr->getAttributes()->add('class', 'disabled');
        }

        return $tr;
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'uuid'        => 'o.uuid',
                'object_name' => 'o.object_name',
                'committed'   => 'vdu.committed',
                'uncommitted' => 'vdu.uncommitted',
                'ts_updated'  => 'vdu.ts_updated',
            ]
        )->join(
            ['vdu' => 'vm_datastore_usage'],
            'vdu.vm_uuid = o.uuid',
            []
        )->where('vdu.datastore_uuid = ?', $this->uuid)->order('object_name ASC');

        return $query;
    }
}
