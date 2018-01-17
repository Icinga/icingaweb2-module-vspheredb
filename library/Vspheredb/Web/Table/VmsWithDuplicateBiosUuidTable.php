<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class VmsWithDuplicateBiosUuidTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected $searchColumns = [
        'object_name',
    ];

    protected $property = 'bios_uuid';

    protected $propertyTitle;

    protected $lastValue;

    public function getColumnsToBeRendered()
    {
        return [
            $this->propertyTitle ?: $this->translate('Bios UUID'),
            $this->translate('Name'),
        ];
    }

    public function setProperty($name, $title)
    {
        $this->property = $name;
        $this->propertyTitle = $title;

        return $this;
    }

    public function renderRow($row)
    {
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            ['id' => $row->id]
        );

        $value = $row->{$this->property};

        if ($value === $this->lastValue) {
            $tr = $this::row([
                '',
                $caption,
            ]);
        } else {
            $tr = $this::row([
                $value,
                $caption,
            ]);
            $this->lastValue = $value;
        }
        $tr->attributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }

    public function prepareQuery()
    {
        $db = $this->db();

        $property = $this->property;
        $this->searchColumns[] = $property;
        $duplicateQuery = $db->select()->from('virtual_machine', $property)
            ->group($property)
            ->having('(COUNT(*) > 1)');

        return $db->select()->from(
            ['vm' => 'virtual_machine'],
            [
                'o.id',
                'vm.guest_host_name',
                "vm.$property",
                'vm.runtime_power_state',
                'o.overall_status',
            ]
        )->join(
            ['o' => 'object'],
            'o.id = vm.id',
            ['o.object_name']
        )->where(
            "$property IN (?)", $duplicateQuery
        )->order($property)->order('object_name');
    }
}
