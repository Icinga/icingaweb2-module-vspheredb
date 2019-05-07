<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;

abstract class ObjectsTable extends BaseTable
{
    protected $searchColumns = [
        'object_name',
    ];

    /** @var VCenter */
    protected $filterVCenter;

    protected $parentUuids;

    protected $baseUrl;

    protected $overallStatusRenderer;

    public function filterParentUuids(array $uuids)
    {
        $this->parentUuids = $uuids;

        return $this;
    }

    public function filterVCenter(VCenter $vCenter)
    {
        $this->filterVCenter = $vCenter;

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
                    ['uuid' => bin2hex($row->uuid)]
                );
            }

            return $result;
        });
    }
}
