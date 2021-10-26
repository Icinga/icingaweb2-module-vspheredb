<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;
use JsonSerializable;

class CustomValues implements JsonSerializable
{
    protected $values = [];

    public static function create(array $values = null)
    {
        return new static($values);
    }

    public static function fromJson($string)
    {
        if (\strlen($string) > 0) {
            return new static((array) JsonString::decode($string));
        } else {
            return new static;
        }
    }

    public function isEmpty()
    {
        return empty($this->values);
    }

    public function has($key)
    {
        return \array_key_exists($key, $this->values);
    }

    public function remove($key)
    {
        unset($this->values[$key]);
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->values[$key];
        } else {
            return $default;
        }
    }

    public function jsonSerialize()
    {
        return (object) $this->values;
    }

    public function toArray()
    {
        return $this->values;
    }

    protected function __construct(array $values = null)
    {
        if ($values === null) {
            return;
        } else {
            $this->values = $values;
        }
    }
}
