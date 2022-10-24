<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;
use Icinga\Module\Vspheredb\Web\Table\TableWithVCenterFilter;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Ramsey\Uuid\Uuid;

abstract class ObjectsTable extends BaseTable implements TableWithVCenterFilter
{
    protected $searchColumns = [
        'object_name',
    ];

    /** @deprecated  */
    protected $filterVCenter;

    /** @deprecated  */
    protected $parentUuids;

    protected $baseUrl;

    protected $overallStatusRenderer;

    public function filterParentUuids(array $uuids)
    {
        $this->getQuery()->where('o.parent_uuid IN (?)', $uuids);

        return $this;
    }

    public function filterVCenter(VCenter $vCenter): self
    {
        return $this->filterVCenterUuids([$vCenter->getUuid()]);
    }

    public function filterVCenterUuids(array $uuids): self
    {
        if (empty($uuids)) {
            $this->getQuery()->where('1 = 0');
            return $this;
        }

        $db = $this->db();
        if ($this instanceof VCenterSummaryTable) {
            $column = 'vc.instance_uuid';
        } else {
            $column = 'o.vcenter_uuid';
        }
        if (count($uuids) === 1) {
            $this->getQuery()->where("$column = ?", DbUtil::quoteBinaryCompat(array_shift($uuids), $db));
        } else {
            $this->getQuery()->where("$column IN (?)", DbUtil::quoteBinaryCompat($uuids, $db));
        }

        return $this;
    }

    protected function overallStatusRenderer()
    {
        if ($this->overallStatusRenderer === null) {
            $this->overallStatusRenderer = new OverallStatusRenderer();
        }

        return $this->overallStatusRenderer;
    }

    protected function createOverallStatusColumn()
    {
        return $this->createColumn('overall_status', $this->translate('Status'), 'o.overall_status')
            ->setRenderer($this->overallStatusRenderer())
            ->setDefaultSortDirection('DESC');
    }

    protected function createObjectNameColumn()
    {
        return $this->createColumn('object_name', $this->translate('Name'), [
            'object_name'    => 'o.object_name',
            'overall_status' => 'o.overall_status',
            'uuid'           => 'o.uuid',
        ])->setRenderer(function ($row) {
            if (in_array('overall_status', $this->getChosenColumnNames())) {
                $result = [];
            } else {
                $statusRenderer = $this->overallStatusRenderer();
                $result = [$statusRenderer($row)];
            }
            if ($this->baseUrl === null) {
                $result[] = $row->object_name;
            } else {
                $result[] = Link::create(
                    $row->object_name,
                    $this->baseUrl,
                    ['uuid' => Uuid::fromBytes($row->uuid)->toString()]
                );
            }

            return $result;
        });
    }
}
