<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db\DbObject;

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
     * @param object|string|null $val  The value to extract data from
     * @param array       $keys A list of nested keys pointing to desired data
     *
     * @return mixed
     */
    public static function getDeepValue(object|string|null $val, array $keys): mixed
    {
        if ($val === null) {
            return null;
        }
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
     * @param object $row stdClass object providing property values
     * @param string $var Variable/property name
     *
     * @return mixed
     */
    public static function getSpecificValue(object $row, string $var): mixed
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
                return null;
                // Hint: we used to throw exceptions
                // throw new RuntimeException('Data is not nested, cannot access %s: %s', $var, var_export($row, 1));
            }

            return static::getDeepValue($row->$main, $parts);
        }
    }
}
