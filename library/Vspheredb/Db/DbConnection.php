<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Data\Db\DbConnection as IcingaDbConnection;
use RuntimeException;
use Zend_Db_Expr;

use function array_map;
use function bin2hex;
use function is_array;

class DbConnection extends IcingaDbConnection
{
    /**
     * @return bool
     */
    public function isMysql(): bool
    {
        return $this->getDbType() === 'mysql';
    }

    /**
     * @return bool
     */
    public function isPgsql(): bool
    {
        return $this->getDbType() === 'pgsql';
    }

    /**
     * @param string|array $binary
     *
     * @return Zend_Db_Expr|array|string
     */
    public function quoteBinary(string|array $binary): Zend_Db_Expr|array|string
    {
        if ($binary === '') {
            return '';
        }

        if (is_array($binary)) {
            return array_map([$this, 'quoteBinary'], $binary);
        }

        if ($this->isPgsql()) {
            return new Zend_Db_Expr("'\\x" . bin2hex($binary) . "'");
        }

        return new Zend_Db_Expr('0x' . bin2hex($binary));
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPgExtension(string $name): bool
    {
        $db = $this->getDbAdapter();
        $query = $db->select()->from(
            ['e' => 'pg_extension'],
            ['cnt' => 'COUNT(*)']
        )->where('extname = ?', $name);

        return (int) $db->fetchOne($query) === 1;
    }

    /**
     * @param $binary
     *
     * @return Zend_Db_Expr
     *
     * @throws RuntimeException
     */
    public static function pgBinEscape($binary): Zend_Db_Expr
    {
        if ($binary instanceof Zend_Db_Expr) {
            throw new RuntimeException('Trying to escape binary twice');
        }

        return new Zend_Db_Expr("'\\x" . bin2hex($binary) . "'");
    }
}
