<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\Web\Table\BaseTable;

abstract class ObjectsTable extends BaseTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentUuids;

    public function filterParentUuids(array $uuids)
    {
        $this->parentUuids = $uuids;

        return $this;
    }
}
