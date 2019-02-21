<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Html;
use dipl\Html\Img;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;

class VmsGuestDiskUsageTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    protected $searchColumns = [
        'object_name',
        'disk_path',
    ];

    protected $withHistory = true;

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createObjectNameColumn(),
            $this->createColumn('disk_path', $this->translate('Disk Path'), 'vdu.disk_path'),
            $this->createColumn('free_space', $this->translate('Free Space'), 'vdu.free_space')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->free_space);
                }),
            $this->createColumn('capacity', $this->translate('Capacity'), 'vdu.capacity')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->capacity);
                }),
            $this->createColumn('usage', $this->translate('Usage'), [
                'free_space' => 'vdu.free_space',
                'capacity'   => 'vdu.capacity',
            ])->setRenderer(function ($row) {
                $title = sprintf(
                    '%s free out of %s (%.2F %%)',
                    $row->free_space,
                    $row->capacity,
                    $row->free_space / $row->capacity
                );

                return new SimpleUsageBar($row->capacity - $row->free_space, $row->capacity, $title);
            })->setSortExpression(
                '1 - (vdu.free_space / vdu.capacity)'
            )->setDefaultSortDirection('DESC'),
        ]);

        if ($this->withHistory) {
            $this->addAvailableColumn(
                $this->createColumn('history', $this->translate('History'), [
                    'object_name' => 'o.object_name',
                ])->setRenderer(function ($row) {
                    $ciName = str_replace(' ', '_', $row->object_name);
                    $path = str_replace('/', '_', $row->disk_path);
                    $path = str_replace(' ', '_', $path);
                    $ci = $ciName . ':' . $path;
                    $now = time();
                    $end = floor($now / 60) * 60;
                    $offset = 3600 * 24 * 96;
                    $duration = 3600 * 12 * 4;
                    $start = $end - $offset;
                    $end = $start + $duration;

                    return Html::tag('div', [
                        'style' => 'position: relative'
                    ], Html::tag('div', [
                        'class' => 'inline-perf-container'
                    ], Img::create('rrd/img', [
                        'file'     => $ci . '.rrd',
                        'rnd'      => time(),
                        'height'   => 24 * 6,
                        'width'    => 80 * 6,
                        'start'    => $start,
                        'end'      => $end,
                        'template' => 'vm_disk',
                    ], [
                        'class' => 'inline-perf-small'
                    ])));
                })
            );
        }
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        )->join(
            ['vdu' => 'vm_disk_usage'],
            'vc.uuid = vdu.vm_uuid',
            []
        );

        return $query;
    }

    public function getDefaultColumnNames()
    {
        return [
            'object_name',
            'disk_path',
            'capacity',
            'usage',
        ];
    }
}
