<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Web\Table\ZfQueryBasedTable;

abstract class ObjectsTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentIds;

    public function filterParentIds(array $ids)
    {
        $this->parentIds = $ids;

        return $this;
    }
}
