<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\TopPerfTable;

class TopController extends Controller
{
    public function init()
    {
        $this->assertPermission('vspheredb/admin');
    }

    public function vmsAction()
    {
        $this->setAutorefreshInterval(10);
        $parentId = $this->params->get('parent_uuid');
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

    protected function fetchTop($counterUuid, $parentUuid = null)
    {
        $query = $this->fetchTopQuery($counterUuid);
        if ($parentUuid !== null) {
            $query->where('o.parent_uuid = ?', (int) $parentUuid);
        }
        return $this->db()->getDbAdapter()->fetchAll($query);
    }

    protected function fetchTopPerParent($counterUuid, $agg)
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
            'o.uuid = c.object_uuid',
            [
                'o.uuid',
                'o.overall_status',
            ]
        )->join(
            ['p' => 'object'],
            'o.parent_uuid = p.uuid',
            [
                'object_uuid' => 'p.uuid',
                'object_name' => 'p.object_name',
            ]
        )->where('counter_key = ?', (int) $counterUuid)
            ->group('p.uuid')
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));

        return $db->fetchAll($query);
    }

    protected function fetchTopQuery($counterId)
    {
        return $this->db()->getDbAdapter()->select()->from(
            ['c' => 'counter_300x5'],
            [
                'c.object_uuid',
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
            'o.uuid = c.object_uuid',
            [
                'o.uuid',
                'object_name' => 'o.object_name',
                'o.overall_status',
            ]
        )->where('counter_key = ?', (int) $counterId)
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));
    }

    protected function makeTopTable($title, $rows, $format, $link)
    {
        return new TopPerfTable($title, $rows, $format, $link);
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
}
