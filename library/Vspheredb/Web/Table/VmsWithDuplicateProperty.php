<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\HtmlElement;
use Zend_Db_Select;

class VmsWithDuplicateProperty extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next'
    ];

    protected $searchColumns = ['object_name'];

    protected ?string $property = null;

    protected ?string $propertyTitle = null;

    protected ?string $lastValue = null;

    public static function create(Db $db, string $property, string $title): static
    {
        return (new static($db))->setProperty($property, $title);
    }

    public function getColumnsToBeRendered(): array
    {
        return [$this->propertyTitle, $this->translate('Name')];
    }

    public function getColor(): string
    {
        if (count($this) > 0) {
            return 'yellow';
        }

        return 'green';
    }

    public function setProperty(string $name, string $title): static
    {
        $this->property = $name;
        $this->propertyTitle = $title;

        return $this;
    }

    public function renderRow($row): HtmlElement
    {
        $caption = Link::create($row->object_name, 'vspheredb/vm', Util::uuidParams($row->uuid));

        $value = $row->{$this->property};

        if ($value === $this->lastValue) {
            $tr = $this::row(['', $caption]);
        } else {
            $tr = $this::row([$value, $caption]);
            $this->lastValue = $value;
        }
        $tr->getAttributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }

    public function prepareQuery(): Select|Zend_Db_Select
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
                'o.overall_status'
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
