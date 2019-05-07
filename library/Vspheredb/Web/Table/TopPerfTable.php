<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use ipl\Html\Html;
use ipl\Html\Table;

class TopPerfTable extends Table
{
    protected $defaultAttributes = [
        'class' => 'common-table table-row-selectable',
        'data-base-target' => '_next',
    ];

    public function __construct($title, $rows, $format, $link)
    {
        $this->header()->add(Table::tr([
            Table::th($title),
            Table::th('5x5min')->addAttributes(['style' => 'width: 6em']),
            Table::th('Last 5min')->addAttributes(['style' => 'width: 10em'])
        ]));
        foreach ($rows as $row) {
            $this->body()->add(Table::row([
                $this->$link($row),
                $this->makeSparkLine($row),
                $format ? $this->$format($row->value_last) : $row->value_last,
            ]));
        }
    }

    protected function createVmLink($row)
    {
        $name = $row->object_name;
        if (property_exists($row, 'instance') && strlen($row->instance)) {
            $name .= ': ' . $row->instance;
        }

        return Link::create(
            $name,
            'vspheredb/vm',
            ['uuid' => bin2hex($row->object_uuid)]
        );
    }

    protected function createTopForParentLink($row)
    {
        return Link::create(
            $row->object_name,
            'vspheredb/top/vms',
            ['parent_uuid' => bin2hex($row->object_uuid)]
        );
    }

    protected function formatMicroSeconds($num)
    {
        if ($num > 500) {
            return sprintf('%0.2Fms', $num / 1000);
        } else {
            return sprintf('%dµs', $num);
        }
    }

    protected function formatKiloBytesPerSecond($num)
    {
        $num *= 8;
        if ($num > 500000) {
            return sprintf('%0.2F Gbit/s', $num / 1024 / 1024);
        } elseif ($num > 500) {
            return sprintf('%0.2F Mbit/s', $num / 1024);
        } else {
            return sprintf('%0.2F Kbit/s', $num);
        }
    }

    protected function makeSparkLine($row)
    {
        $values = [
            $row->value_minus4,
            $row->value_minus3,
            $row->value_minus2,
            $row->value_minus1,
            $row->value_last,
        ];

        return Html::tag('span', [
            'class'            => 'sparkline',
            'sparkType'        => 'bar',
            'sparkBarColor'    => '#44bb77',
            'sparkNegBarColor' => '#0095BF',
            'sparkBarWidth'    => 7,
            'values'           => implode(',', $values)
        ]);
    }
}
