<?php

namespace Icinga\Module\Vspheredb\Web\Table;

interface TableWithParentFilter
{
    public function filterParentUuids(array $uuids);
}
