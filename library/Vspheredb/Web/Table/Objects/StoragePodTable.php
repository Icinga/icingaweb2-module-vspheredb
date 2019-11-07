<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Util\Format;
use ipl\Html\Html;

class StoragePodTable extends ObjectsTable
{
    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createColumn('object_name', $this->translate('Name'), [
                'object_name'          => 'o.object_name',
                'uuid'                 => 'o.uuid',
                'cnt_datastore'        => 'COUNT(dso.uuid)',
            ])->setRenderer(function ($row) {
                $cntDs = (int) $row->cnt_datastore;
                if ($cntDs === 0) {
                    $dsCount = 'no datastore';
                } elseif ($cntDs === 1) {
                    $dsCount = '1 datastore';
                } else {
                    $dsCount = sprintf($this->translate('%s datastores'), $cntDs);
                }
                return Link::create(
                    [
                        $row->object_name,
                        ' ',
                        Html::tag('small', $dsCount)
                    ],
                    'vspheredb/datastores',
                    ['uuid' => bin2hex($row->uuid)]
                );
            }),
            $this->createColumn('free_space', $this->translate('Free'), 'sp.free_space')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->free_space, Format::STANDARD_IEC);
                }),
            $this->createColumn('free_space_percent', $this->translate('Free (%)'), [
                'free_space_percent'  => '(sp.free_space / sp.capacity) * 100'
            ])->setRenderer(function ($row) {
                return $this->formatPercent($row->free_space_percent);
            }),
            $this->createColumn('size', $this->translate('Size'), 'sp.capacity')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->capacity, Format::STANDARD_IEC);
                }),
            $this->createColumn('usage', $this->translate('Usage'), [
                'uuid'       => 'o.uuid',
                'free_space' => 'sp.free_space',
                'capacity'   => 'sp.capacity',
            ])->setRenderer(function ($row) {
                /** @var Db $connection */
                $div = 1024 * 1024;
                $usage = new MemoryUsage(($row->capacity - $row->free_space) / $div, $row->capacity / $div);

                return $usage;
            })->setSortExpression(
                '1 - (sp.free_space / sp.capacity)'
            )->setDefaultSortDirection('DESC'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'object_name',
            'usage',
        ];
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

    public function sortBy($columns)
    {
        parent::sortBy($columns);

        $this->getQuery()->order('object_name');

        return $this;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['sp' => 'storage_pod'],
            'o.uuid = sp.uuid',
            []
        )->joinLeft(
            ['dso' => 'object'],
            'dso.parent_uuid= o.uuid',
            []
        )->group('o.uuid');

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('o.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        return $query;
    }
}
