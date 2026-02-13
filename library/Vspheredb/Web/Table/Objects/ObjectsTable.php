<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;
use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use Icinga\Module\Vspheredb\Web\Table\TableWithParentFilter;
use Icinga\Module\Vspheredb\Web\Table\TableWithVCenterFilter;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Ramsey\Uuid\Uuid;

abstract class ObjectsTable extends BaseTable implements TableWithVCenterFilter, TableWithParentFilter
{
    protected $searchColumns = ['object_name'];

    /** @deprecated  */
    protected $filterVCenter;

    /** @deprecated  */
    protected $parentUuids;

    protected ?string $baseUrl = null;

    protected ?OverallStatusRenderer $overallStatusRenderer = null;

    public function filterParentUuids(array $uuids): static
    {
        $this->getQuery()->where('o.parent_uuid IN (?)', $uuids);

        return $this;
    }

    public function filterVCenter(VCenter $vCenter): static
    {
        return $this->filterVCenterUuids([$vCenter->getUuid()]);
    }

    public function filterVCenterUuids(array $uuids): static
    {
        if (empty($uuids)) {
            $this->getQuery()->where('1 = 0');

            return $this;
        }

        $db = $this->db();
        $column = $this instanceof VCenterSummaryTable ? 'vc.instance_uuid' : 'o.vcenter_uuid';
        if (count($uuids) === 1) {
            $this->getQuery()->where("$column = ?", DbUtil::quoteBinaryCompat(array_shift($uuids), $db));
        } else {
            $this->getQuery()->where("$column IN (?)", DbUtil::quoteBinaryCompat($uuids, $db));
        }

        return $this;
    }

    protected function overallStatusRenderer(): OverallStatusRenderer
    {
        return $this->overallStatusRenderer ??= new OverallStatusRenderer();
    }

    protected function createOverallStatusColumn(): SimpleColumn
    {
        return $this->createColumn('overall_status', $this->translate('Status'), 'o.overall_status')
            ->setRenderer($this->overallStatusRenderer())
            ->setDefaultSortDirection('DESC');
    }

    protected function createObjectNameColumn(): SimpleColumn
    {
        return $this->createColumn('object_name', $this->translate('Name'), [
            'object_name'    => 'o.object_name',
            'overall_status' => 'o.overall_status',
            'uuid'           => 'o.uuid'
        ])->setRenderer(function ($row) {
            $row->object_name = Anonymizer::anonymizeString($row->object_name);
            if (in_array('overall_status', $this->getChosenColumnNames())) {
                $result = [];
            } else {
                $statusRenderer = $this->overallStatusRenderer();
                $result = [$statusRenderer($row)];
            }
            $result[] = $this->baseUrl === null
                ? $row->object_name
                : Link::create($row->object_name, $this->baseUrl, ['uuid' => Uuid::fromBytes($row->uuid)->toString()]);

            return $result;
        });
    }
}
