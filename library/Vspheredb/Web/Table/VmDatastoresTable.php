<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class VmDatastoresTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentIds;

    /** @var VirtualMachine */
    protected $vm;

    /** @var int */
    protected $id;

    public static function create(VirtualMachine $vm)
    {
        $tbl = new static($vm->getConnection());
        return $tbl->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->id = $vm->get('id');
        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Datastore'),
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
            'vspheredb/datastore',
            ['id' => $row->id],
            ['title' => sprintf(
                $this->translate('Datastore: %s'),
                $row->object_name
            )]
        );

        /** @var Db $connection */
        $connection = $this->connection();
        $datastore = Datastore::load($row->id, $connection);
        $usage = new DatastoreUsage($datastore);
        $usage->setCapacity($size);
        $usage->attributes()->add('class', 'compact');
        $usage->addDiskFromDbRow($row);
        $dsUsage = new DatastoreUsage($datastore);
        $dsUsage->attributes()->add('class', 'compact');
        $dsUsage->addDiskFromDbRow($row);

        $tr = $this::tr([
            // TODO: move to CSS
            $this::td($caption, ['style' => 'overflow: hidden; display: inline-block; height: 2em; min-width: 8em;']),
            $this::td(Format::bytes($size, Format::STANDARD_IEC), ['style' => 'white-space: pre;']),
            $this::td($usage, ['style' => 'width: 25%;']),
            $this::td($dsUsage, ['style' => 'width: 25%;'])
        ]);

        return $tr;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'id'          => 'o.id',
                'object_name' => 'o.object_name',
                'committed'   => 'vdu.committed',
                'uncommitted' => 'vdu.uncommitted',
            ]
        )->join(
            ['vdu' => 'vm_datastore_usage'],
            'vdu.datastore_id = o.id',
            []
        )->where('vdu.vm_id = ?', $this->id)->order('object_name ASC');

        return $query;
    }
}
