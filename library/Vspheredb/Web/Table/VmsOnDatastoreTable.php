<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;

class VmsOnDatastoreTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    /** @var Datastore */
    protected $datastore;

    /** @var string */
    protected $uuid;

    /** @var int */
    protected $capacity;

    /** @var int */
    protected $uncommitted;

    public static function create(Datastore $datastore)
    {
        $tbl = new static($datastore->getConnection());
        return $tbl->setDatastore($datastore);
    }

    protected function setDatastore(Datastore $datastore)
    {
        $this->datastore   = $datastore;
        $this->uuid        = $datastore->get('uuid');
        $this->capacity    = $datastore->get('capacity');
        $this->uncommitted = $datastore->get('uncommitted');

        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Virtual Machine'),
            $this->translate('Size'),
            $this->translate('Usage'),
            $this->translate('On Datastore'),
        ];
    }

    public function renderRow($row)
    {
        $size = $row->committed + $row->uncommitted;
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            ['uuid' => bin2hex($row->uuid)],
            ['title' => sprintf(
                $this->translate('Virtual Machine: %s'),
                $row->object_name
            )]
        );

        $usage = new DatastoreUsage($this->datastore);
        $usage->setCapacity($size);
        $usage->getAttributes()->add('class', 'compact');
        $usage->addDiskFromDbRow($row);
        $dsUsage = new DatastoreUsage($this->datastore);
        $dsUsage->getAttributes()->add('class', 'compact');
        $dsUsage->addDiskFromDbRow($row);

        $tr = $this::tr([
            // TODO: move to CSS
            $this::td($caption, ['style' => 'overflow: hidden; display: inline-block; height: 2em; min-width: 8em;']),
            $this::td(Format::bytes($size, Format::STANDARD_IEC), ['style' => 'white-space: pre;']),
            $this::td($usage, ['style' => 'width: 25%;']),
            $this::td($dsUsage, ['style' => 'width: 25%;'])
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

    public function prepareQuery()
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
