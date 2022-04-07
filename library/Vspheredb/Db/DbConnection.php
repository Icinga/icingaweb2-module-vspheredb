<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Data\Db\DbConnection as IcingaDbConnection;
use Icinga\Module\Director\Db\DbUtil;
use RuntimeException;
use Zend_Db_Expr;
use function array_map;
use function bin2hex;
use function is_array;

class DbConnection extends IcingaDbConnection
{
    public function isMysql()
    {
        return $this->getDbType() === 'mysql';
    }

    public function isPgsql()
    {
        return $this->getDbType() === 'pgsql';
    }

    public function quoteBinary($binary)
    {
        if (is_array($binary)) {
            return array_map([$this, 'quoteBinary'], $binary);
        }

        if ($this->isPgsql()) {
            return new Zend_Db_Expr("'\\x" . bin2hex($binary) . "'");
        }

        return new Zend_Db_Expr('0x' . bin2hex($binary));
    }

    public function hasPgExtension($name)
    {
        $db = $this->getDbAdapter();
        $query = $db->select()->from(
            array('e' => 'pg_extension'),
            array('cnt' => 'COUNT(*)')
        )->where('extname = ?', $name);

        return (int) $db->fetchOne($query) === 1;
    }

    public static function pgBinEscape($binary)
    {
        if ($binary instanceof Zend_Db_Expr) {
            throw new RuntimeException('Trying to escape binary twice');
        }

        return new Zend_Db_Expr("'\\x" . bin2hex($binary) . "'");
    }
}
