<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;

abstract class ObjectsTable extends BaseTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentUuids;

    protected $baseUrl;

    public function filterParentUuids(array $uuids)
    {
        $this->parentUuids = $uuids;

        return $this;
    }

    protected function createMorefColumn()
    {
        return $this->createColumn('moref', 'MO Ref')
            ->setRenderer(function ($row) {
                return $this->linkToVCenter($row->moref);
            });
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
            ->setRenderer(function ($row) {
                return Icon::create('ok', [
                    'title' => $this->getStatusDescription($row->overall_status),
                    'class' => [ 'state', $row->overall_status ]
                ]);
            })->setDefaultSortDirection('DESC');
    }

    protected function createObjectNameColumn()
    {
        return $this->createColumn('object_name', 'Name', [
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

    protected function getStatusDescription($status)
    {
        $descriptions = [
            'gray'   => $this->translate('Gray - status is unknown'),
            'green'  => $this->translate('Green - everything is fine'),
            'yellow' => $this->translate('Yellow - there are warnings'),
            'red'    => $this->translate('Red - there is a problem'),
        ];

        return $descriptions[$status];
    }
}
