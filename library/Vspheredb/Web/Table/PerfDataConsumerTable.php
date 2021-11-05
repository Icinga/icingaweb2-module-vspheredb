<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use Ramsey\Uuid\Uuid;

class PerfDataConsumerTable extends BaseTable
{
    protected $keyColumn = 'uuid';

    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected function initialize()
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

    public function prepareQuery()
    {
        return $this->db()->select()->from(
            ['pc' => 'perfdata_consumer'],
            $this->getRequiredDbColumns()
        )->order('name');
    }
}
