<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Util\Format;
use ipl\Html\Html;
use Zend_Db_Select;

class StoragePodTable extends PercentObjectsTable
{
    protected function initialize(): void
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),

            $this->createColumn('object_name', $this->translate('Name'), [
                'object_name'   => 'o.object_name',
                'uuid'          => 'o.uuid',
                'cnt_datastore' => 'COUNT(dso.uuid)'
            ])
                ->setRenderer(function ($row) {
                    $cntDs = (int) $row->cnt_datastore;
                    $dsCount = match ($cntDs) {
                        0       => 'no datastore',
                        1       => '1 datastore',
                        default => sprintf($this->translate('%s datastores'), $cntDs)
                    };

                    return Link::create(
                        [$row->object_name, ' ', Html::tag('small', $dsCount)],
                        'vspheredb/datastores',
                        Util::uuidParams($row->uuid)
                    );
                }),

            $this->createColumn('free_space', $this->translate('Free'), 'sp.free_space')
                ->setRenderer(fn($row) => Format::bytes($row->free_space, Format::STANDARD_IEC)),

            $this->createColumn('free_space_percent', $this->translate('Free (%)'), [
                'free_space_percent' => '(sp.free_space / sp.capacity) * 100'
            ])
                ->setRenderer(fn($row) => $this->formatPercent($row->free_space_percent)),

            $this->createColumn('size', $this->translate('Size'), 'sp.capacity')
                ->setRenderer(fn($row) => Format::bytes($row->capacity, Format::STANDARD_IEC)),

            $this->createColumn('usage', $this->translate('Usage'), [
                'uuid'       => 'o.uuid',
                'free_space' => 'sp.free_space',
                'capacity'   => 'sp.capacity'
            ])
                ->setRenderer(function ($row) {
                    $div = 1024 * 1024;

                    return new MemoryUsage(($row->capacity - $row->free_space) / $div, $row->capacity / $div);
                })
                ->setSortExpression('1 - (sp.free_space / sp.capacity)')
                ->setDefaultSortDirection('DESC')
        ]);
    }

    public function getDefaultColumnNames(): array
    {
        return [
            'overall_status',
            'object_name',
            'usage'
        ];
    }

    public function sortBy(array|string $columns): static
    {
        parent::sortBy($columns);

        $this->getQuery()->order('object_name');

        return $this;
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()
            ->from(['o' => 'object'], $this->getRequiredDbColumns())
            ->join(['sp' => 'storage_pod'], 'o.uuid = sp.uuid', [])
            ->joinLeft(['dso' => 'object'], 'dso.parent_uuid= o.uuid', [])
            ->group('o.uuid');
    }
}
