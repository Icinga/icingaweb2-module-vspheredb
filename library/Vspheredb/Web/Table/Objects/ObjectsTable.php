<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Web\Table\ZfQueryBasedTable;

abstract class ObjectsTable extends ZfQueryBasedTable
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
