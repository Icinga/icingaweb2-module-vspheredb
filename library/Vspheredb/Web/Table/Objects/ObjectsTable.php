<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Link;
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

    public function getQuery()
    {
        $query = parent::getQuery();
        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('o.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        return $query;
    }

    protected function createMorefColumn()
    {
        return $this->createColumn('moref', 'MO Ref')
            ->setRenderer(function ($row) {
                return $this->linkToVCenter($row->moref);
            });
    }

    protected function overallStatusRenderer()
    {
        if ($this->overallStatusRenderer === null) {
            $this->overallStatusRenderer = new OverallStatusRenderer();
        }

        return $this->overallStatusRenderer;
    }

    protected function linkToVCenter($moRef)
    {
        return Html::tag('a', [
            'href' => sprintf(
                'https://%s/mob/?moid=%s',
                $this->vCenter->getFirstServer()->get('host'),
                rawurlencode($moRef)
            ),
            'target' => '_blank',
            'title' => $this->translate('Jump to the Managed Object browser')
        ], $moRef);
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
            'object_name' => 'o.object_name',
            'uuid'        => 'o.uuid',
        ])->setRenderer(function ($row) {
            if ($this->baseUrl === null) {
                return $row->object_name;
            } else {
                return Link::create(
                    $row->object_name,
                    $this->baseUrl,
                    ['uuid' => bin2hex($row->uuid)]
                );
            }
        });
    }
}
