<?php

namespace Icinga\Module\Vspheredb\Db;

use Zend_Db_Adapter_Abstract as ZfDb;
use Zend_Db_Select;

class QueryHelper
{
    /**
     * @param ZfDb $db
     * @param Zend_Db_Select $query
     * @param string $column
     * @param ?array $vCenterFilterUuids
     *
     * @return void
     */
    public static function applyOptionalVCenterFilter(
        ZfDb $db,
        Zend_Db_Select $query,
        string $column,
        ?array $vCenterFilterUuids
    ): void {
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
