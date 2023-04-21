<?php

namespace Icinga\Module\Vspheredb\Db;

use Zend_Db_Adapter_Abstract as ZfDb;

class QueryHelper
{
    public static function applyOptionalVCenterFilter(ZfDb $db, $query, string $column, ?array $vCenterFilterUuids)
    {
        if ($vCenterFilterUuids === null) {
            return;
        }
        if (empty($vCenterFilterUuids)) {
            $query->where('1 = 0');
            return;
        }

        if (count($vCenterFilterUuids) === 1) {
            $query->where("$column = ?", DbUtil::quoteBinaryCompat(array_shift($vCenterFilterUuids), $db));
        } else {
            $query->where("$column IN (?)", DbUtil::quoteBinaryCompat($vCenterFilterUuids, $db));
        }
    }
}
