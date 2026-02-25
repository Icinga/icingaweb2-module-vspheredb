<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;
use JsonSerializable;

class CustomValues implements JsonSerializable
{
    protected $values = [];

    public static function create(?array $values = null)
    {
        return new static($values);
    }

    public static function fromJson($string)
    {
        return new static((array) JsonString::decodeOptional($string));
    }

    public function isEmpty()
    {
        return empty($this->values);
    }

    public function has($key)
    {
        return \array_key_exists($key, $this->values);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * @param string $key
     * @param $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->values[$key];
        } else {
            return $default;
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) $this->values;
    }

    public function toArray()
    {
        return $this->values;
    }

    protected function __construct(?array $values = null)
    {
        if ($values === null) {
            return;
        } else {
            $this->values = $values;
        }
    }
}
