<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db\DbObject;
use RuntimeException;

class MappingHelper
{

    /**
     * Recursively extract a value from a nested structure
     *
     * For a $val looking like
     *
     * { 'vars' => { 'disk' => { 'sda' => { 'size' => '256G' } } } }
     *
     * and a key vars.disk.sda given as [ 'vars', 'disk', 'sda' ] this would
     * return { size => '255GB' }
     *
     * @param  string $val  The value to extract data from
     * @param  array  $keys A list of nested keys pointing to desired data
     *
     * @return mixed
     */
    public static function getDeepValue($val, array $keys)
    {
        $key = array_shift($keys);
        if (! property_exists($val, $key)) {
            return null;
        }

        if (empty($keys)) {
            return $val->$key;
        }

        return static::getDeepValue($val->$key, $keys);
    }

    /**
     * Return a specific value from a given row object
     *
     * Supports also keys pointing to nested structures like vars.disk.sda
     *
     * @param  object $row stdClass object providing property values
     * @param  string $var  Variable/property name
     * @return mixed
     */
    public static function getSpecificValue($row, $var)
    {
        if (strpos($var, '.') === false) {
            if ($row instanceof DbObject) {
                return $row->$var;
            }
            if (! property_exists($row, $var)) {
                return null;
            }

            return $row->$var;
        } else {
            $parts = explode('.', $var);
            $main = array_shift($parts);
            if (! property_exists($row, $main)) {
                return null;
            }

            if (! is_object($row->$main)) {
                throw new RuntimeException('Data is not nested, cannot access %s: %s', $var, var_export($row, 1));
            }

            return static::getDeepValue($row->$main, $parts);
        }
    }
}
