<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\Db;

class VmsWithDuplicateProperty extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected $searchColumns = [
        'object_name',
    ];

    protected $property;

    protected $propertyTitle;

    protected $lastValue;

    public static function create(Db $db, $property, $title)
    {
        $table = new static($db);
        $table->property = $property;
        $table->propertyTitle = $title;

        return $table;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->propertyTitle,
            $this->translate('Name'),
        ];
    }

    public function getColor()
    {
        if (count($this) > 0) {
            return 'yellow';
        }

        return 'green';
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
            ['uuid' => bin2hex($row->uuid)]
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
        $tr->getAttributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }

    public function prepareQuery()
    {
        $db = $this->db();

        $property = $this->property;
        $this->searchColumns[] = $property;
        $duplicateQuery = $db->select()->from('virtual_machine', $property)
            ->where("$property IS NOT NULL")
            ->group($property)
            ->having('(COUNT(*) > 1)');

        return $db->select()->from(
            ['vm' => 'virtual_machine'],
            [
                'o.uuid',
                'vm.guest_host_name',
                "vm.$property",
                'vm.runtime_power_state',
                'o.overall_status',
            ]
        )->join(
            ['o' => 'object'],
            'o.uuid = vm.uuid',
            ['o.object_name']
        )->join(
            ['dup' => $duplicateQuery],
            "vm.$property = dup.$property",
            []
        )->order($property)->order('object_name');
    }
}
