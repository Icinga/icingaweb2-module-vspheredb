<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use dipl\Html\Link;

class DatastoreTable extends ObjectsTable
{
    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Name'),
            $this->translate('Usage'),
        ];
    }

    public function renderRow($row)
    {
        $title = sprintf(
            '%d VM(s), %s of %s used, %s uncommitted',
            $row->cnt_vm,
            $this->formatBytesPercent($row, 'free_space'),
            Format::bytes($row->capacity, Format::STANDARD_IEC),
            $this->formatBytesPercent($row, 'uncommitted')
        );
        $caption = Link::create(
            $this::tag('nobr', null, sprintf(
                '%s (%s)',
                $row->object_name,
                Format::bytes($row->capacity, Format::STANDARD_IEC)
            )),
            'vspheredb/datastore',
            ['id' => $row->id],
            ['title' => $title]
        );

        /** @var Db $connection */
        $connection = $this->connection();
        $usage = new DatastoreUsage(Datastore::load($row->id, $connection));
        $usage->attributes()->add('class', 'compact');
        $usage->loadAllVmDisks()->addFreeDatastoreSpace();
        $tr = $this::tr([
            $this::td($caption),
            $this::td($usage, ['style' => 'width: 60%;'])
        ]);
        $tr->attributes()->add('class', ['datastore', $row->overall_status]);

        return $tr;
    }

    protected function formatBytesPercent($row, $name)
    {
        $bytes = $row->$name;
        $percent = $row->{"${name}_percent"};
        return sprintf(
            '%s (%s)',
            Format::bytes($bytes, Format::STANDARD_IEC),
            $this->formatPercent($percent)
        );
    }

    protected function formatPercent($value)
    {
        return sprintf('%0.2f%%', $value);
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'id'                   => 'o.id',
                'object_name'          => 'o.object_name',
                'overall_status'       => 'o.overall_status',
                'cnt_vm'               => 'COUNT(*)',
                'capacity'             => 'ds.capacity',
                'free_space'           => 'ds.free_space',
                'uncommitted'          => 'ds.uncommitted',
                'free_space_percent' => '(ds.free_space / ds.capacity) * 100',
                'uncommitted_percent'  => '(ds.uncommitted / ds.capacity) * 100',
            ]
        )->join(
            ['ds' => 'datastore'],
            'o.id = ds.id',
            []
        )->joinLeft(
            ['vdu' => 'vm_datastore_usage'],
            'vdu.datastore_id = ds.id',
            []
        )->group('ds.id')->group('o.id')->order('object_name ASC');

        if ($this->parentIds) {
            $query->where('o.parent_id IN (?)', $this->parentIds);
        }

        return $query;
    }
}
