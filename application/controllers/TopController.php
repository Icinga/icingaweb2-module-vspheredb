<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\TopPerfTable;
use Zend_Db_Select;

class TopController extends Controller
{
    public function init(): void
    {
        $this->assertPermission('vspheredb/admin');
    }

    public function vmsAction(): void
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
            )
        ]);
    }

    public function foldersAction(): void
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
            )
        ]);
    }

    /**
     * @param int $counterUuid
     * @param     $parentUuid
     *
     * @return array|null
     */
    protected function fetchTop(int $counterUuid, $parentUuid = null): ?array
    {
        $query = $this->fetchTopQuery($counterUuid);
        if ($parentUuid !== null) {
            $query->where('o.parent_uuid = ?', (int) $parentUuid);
        }
        return $this->db()->getDbAdapter()->fetchAll($query);
    }

    /**
     * @param int $counterUuid
     * @param string $agg
     *
     * @return array|null
     */
    protected function fetchTopPerParent(int $counterUuid, string $agg): ?array
    {
        $db = $this->db()->getDbAdapter();
        $query = $db->select()->from(
            ['c' => 'counter_300x5'],
            [
                'value_last'   => "$agg(c.value_last)",
                'value_minus1' => "$agg(c.value_minus1)",
                'value_minus2' => "$agg(c.value_minus2)",
                'value_minus3' => "$agg(c.value_minus3)",
                'value_minus4' => "$agg(c.value_minus4)"
            ]
        )->join(
            ['o' => 'object'],
            'o.uuid = c.object_uuid',
            [
                'o.uuid',
                'o.overall_status'
            ]
        )->join(
            ['p' => 'object'],
            'o.parent_uuid = p.uuid',
            [
                'object_uuid' => 'p.uuid',
                'object_name' => 'p.object_name'
            ]
        )->where('counter_key = ?', $counterUuid)
            ->group('p.uuid')
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));

        return $db->fetchAll($query);
    }

    /**
     * @param int $counterId
     *
     * @return Zend_Db_Select
     */
    protected function fetchTopQuery(int $counterId): Zend_Db_Select
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
                'c.value_minus4'
            ]
        )->join(
            ['o' => 'object'],
            'o.uuid = c.object_uuid',
            [
                'o.uuid',
                'object_name' => 'o.object_name',
                'o.overall_status'
            ]
        )->where('counter_key = ?', $counterId)
            ->order('value_last DESC')
            ->limit($this->params->get('limit', 10));
    }

    /**
     * @param string $title
     * @param array|null $rows
     * @param string|null $format
     * @param string $link
     *
     * @return TopPerfTable
     */
    protected function makeTopTable(string $title, ?array $rows, ?string $format, string $link): TopPerfTable
    {
        return new TopPerfTable($title, $rows, $format, $link);
    }

    /**
     * @return void
     */
    protected function makeTabs(): void
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
