<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use Icinga\Module\Vspheredb\DbObject\VCenter;

interface TableWithVCenterFilter
{
    public function filterVCenter(VCenter $vCenter);
    public function filterVCenterUuids(array $uuids);
}
