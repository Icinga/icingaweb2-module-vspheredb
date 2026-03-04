<?php

namespace Icinga\Module\Vspheredb\Db;

use gipfl\ZfDb\Adapter\Adapter;
use gipfl\ZfDb\Adapter\Pdo\Pgsql;
use gipfl\ZfDb\Expr;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Adapter_Pdo_Pgsql;
use Zend_Db_Expr;

use function bin2hex;
use function is_array;
use function is_resource;
use function stream_get_contents;

class DbUtil
{
    /**
     * @param $value
     *
     * @return false|mixed|string
     */
    public static function binaryResult($value): mixed
    {
        if (is_resource($value)) {
            return stream_get_contents($value);
        }

        return $value;
    }

    /**
     * @param array|string|null $binary
     * @param Zend_Db_Adapter_Abstract $db
     *
     * @return Zend_Db_Expr|Zend_Db_Expr[]|null
     */
    public static function quoteBinaryLegacy(
        array|string|null $binary,
        Zend_Db_Adapter_Abstract $db
    ): Zend_Db_Expr|array|null {
        if (is_array($binary)) {
            return static::quoteArray($binary, 'quoteBinaryLegacy', $db);
        }

        if ($binary === null) {
            return null;
        }

        if ($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            return new Zend_Db_Expr("'\\x" . bin2hex($binary) . "'");
        }

        return new Zend_Db_Expr('0x' . bin2hex($binary));
    }

    /**
     * @param array|string|null $binary
     * @param Adapter $db
     *
     * @return Expr|Expr[]|null
     */
    public static function quoteBinary(array|string|null $binary, Adapter $db): Expr|array|null
    {
        if (is_array($binary)) {
            return static::quoteArray($binary, 'quoteBinary', $db);
        }

        if ($binary === null) {
            return null;
        }

        if ($db instanceof Pgsql) {
            return new Expr("'\\x" . bin2hex($binary) . "'");
        }

        return new Expr('0x' . bin2hex($binary));
    }

    /**
     * @param array|string|null $binary
     * @param Zend_Db_Adapter_Abstract|Adapter $db
     *
     * @return Expr|Zend_Db_Expr|Expr[]|Zend_Db_Expr[]|null
     */
    public static function quoteBinaryCompat(
        array|string|null $binary,
        Zend_Db_Adapter_Abstract|Adapter $db
    ): Zend_Db_Expr|Expr|array|null {
        if ($db instanceof Adapter) {
            return static::quoteBinary($binary, $db);
        }

        return static::quoteBinaryLegacy($binary, $db);
    }

    /**
     * @param array $array
     * @param string $method
     * @param Adapter|Zend_Db_Adapter_Abstract $db
     *
     * @return array
     */
    protected static function quoteArray(array $array, string $method, Adapter|Zend_Db_Adapter_Abstract $db): array
    {
        $result = [];
        foreach ($array as $bin) {
            $quoted = static::$method($bin, $db);
            $result[] = $quoted;
        }

        return $result;
    }
}
