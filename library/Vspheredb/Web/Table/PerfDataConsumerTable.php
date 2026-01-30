<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\ZfDb\Select;
use Ramsey\Uuid\Uuid;
use Zend_Db_Select;

class PerfDataConsumerTable extends BaseTable
{
    protected string $keyColumn = 'uuid';

    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected function initialize(): void
    {
        $this->addAvailableColumns([
            (new SimpleColumn('name', $this->translate('Name'), [
                'uuid' => 'pc.uuid',
                'name' => 'pc.name',
            ]))->setRenderer(function ($row) {
                return Link::create($row->name, 'vspheredb/perfdata/consumer', [
                    'uuid' => Uuid::fromBytes($row->uuid)
                ], [
                    'data-base-target' => '_next'
                ]);
            }),
            (new SimpleColumn('implementation', $this->translate('Implementation'), [
                'implementation' => 'pc.implementation',
            ]))->setRenderer(function ($row) {
                return $row->implementation;
            }),
        ]);
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()->from(
            ['pc' => 'perfdata_consumer'],
            $this->getRequiredDbColumns()
        )->order('name');
    }
}
