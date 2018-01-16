<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use dipl\Html\Table;
use Icinga\Module\Vspheredb\Web\Controller;
use dipl\Html\Link;

class TopController extends Controller
{
    public function vmsAction()
    {
        $parentId = $this->params->get('parent_id');
        if ($parentId === null) {
            $this->makeTabs();
        } else {
            $this->addSingleTab('Top VMs');
        }

        $this->content()->add([
            $this->makeTopTable(
                'Top Data receive rate',
                $this->fetchTop(526, $parentId),
                'formatKiloBytesPerSecond',
                'createVmLink'
            ),
            $this->makeTopTable(
                'Data transmit rate',
                $this->fetchTop(527, $parentId),
                'formatKiloBytesPerSecond',
                'createVmLink'
            ),
            $this->makeTopTable(
                'Top Write Latency',
                $this->fetchTop(544, $parentId),
                'formatMicroSeconds',
                'createVmLink'
            ),
            $this->makeTopTable(
                'Top Read Latency',
                $this->fetchTop(543, $parentId),
                'formatMicroSeconds',
                'createVmLink'
            ),
        ]);
    }

    public function foldersAction()
    {
        $this->makeTabs();
        $this->content()->add([
            $this->makeTopTable(
                'Top Total Data receive rate',
                $this->fetchTopPerParent(526, 'SUM'),
                'formatKiloBytesPerSecond',
                'createTopForParentLink'
            ),
            $this->makeTopTable(
                'Top Total Data transmit rate',
                $this->fetchTopPerParent(527, 'SUM'),
                'formatKiloBytesPerSecond',
                'createTopForParentLink'
            ),
            $this->makeTopTable(
                'Top Total Average Read/s',
                $this->fetchTopPerParent(171, 'SUM'),
                null,
                'createTopForParentLink'
            ),
            $this->makeTopTable(
                'Top Total Average Write/s',
                $this->fetchTopPerParent(172, 'SUM'),
                null,
                'createTopForParentLink'
            ),
            $this->makeTopTable(
                'Top Average Write Latency',
                $this->fetchTopPerParent(544, 'AVG'),
                'formatMicroSeconds',
                'createTopForParentLink'
            ),
            $this->makeTopTable(
                'Top Average Read Latency',
                $this->fetchTopPerParent(543, 'AVG'),
                'formatMicroSeconds',
                'createTopForParentLink'
            ),
        ]);
    }

    protected function fetchTop($counterId, $parentId = null)
    {
        $query = $this->fetchTopQuery($counterId);
        if ($parentId !== null) {
            $query->where('o.parent_id = ?', (int) $parentId);
        }
        return $this->db()->getDbAdapter()->fetchAll($query);
    }

    protected function fetchTopPerParent($counterId, $agg)
    {
        $db = $this->db()->getDbAdapter();
        $query = $db->select()->from(
            ['c' => 'counter_300x5'],
            [
                'value_last'   => "$agg(c.value_last)",
                'value_minus1' => "$agg(c.value_minus1)",
                'value_minus2' => "$agg(c.value_minus2)",
                'value_minus3' => "$agg(c.value_minus3)",
                'value_minus4' => "$agg(c.value_minus4)",
            ]
        )->join(
            ['o' => 'object'],
            'o.id = c.object_id',
            [
                'o.id',
                'o.overall_status',
            ]
        )->join(
            ['p' => 'object'],
            'o.parent_id = p.id',
            [
                'object_id' => 'p.id',
                'object_name' => 'p.object_name',
            ]
        )->where('counter_key = ?', (int) $counterId)
            ->group('p.id')
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));

        return $db->fetchAll($query);
    }

    protected function fetchTopQuery($counterId)
    {
        return $this->db()->getDbAdapter()->select()->from(
            ['c' => 'counter_300x5'],
            [
                'c.object_id',
                'c.instance',
                'c.ts_last',
                'c.value_last',
                'c.value_minus1',
                'c.value_minus2',
                'c.value_minus3',
                'c.value_minus4',
            ]
        )->join(
            ['o' => 'object'],
            'o.id = c.object_id',
            [
                'o.id',
                'object_name' => 'o.object_name',
                'o.overall_status',
            ]
        )->where('counter_key = ?', (int) $counterId)
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));
    }

    protected function makeTopTable($title, $rows, $format, $link)
    {
        $table = new Table;
        $table->addAttributes([
            'class' => 'common-table table-row-selectable',
            'data-base-target' => '_next',
        ]);
        $table->header()->add(Table::tr([
            Table::th($title),
            Table::th('5x5min')->addAttributes(['style' => 'width: 6em']),
            Table::th('Last 5min')->addAttributes(['style' => 'width: 10em'])
        ]));
        foreach ($rows as $row) {
            $table->body()->add(Table::row([
                $this->$link($row),
                $this->makeSparkLine($row),
                $format ? $this->$format($row->value_last) : $row->value_last,
            ]));
        }

        return $table;
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
            ['id' => $row->object_id]
        );
    }

    protected function createTopForParentLink($row)
    {
        return Link::create(
            $row->object_name,
            'vspheredb/top/vms',
            ['parent_id' => $row->object_id]
        );
    }

    protected function makeTabs()
    {
        $this->tabs()->add('vms', [
            'label' => 'Top VMs',
            'url'   => 'vspheredb/top/vms'
        ])->add('folders', [
            'label' => 'Top Folders',
            'url'   => 'vspheredb/top/folders'
        ])->activate($this->getRequest()->getActionName());
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
