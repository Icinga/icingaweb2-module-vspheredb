<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;
use JsonSerializable;
use ReturnTypeWillChange;

class CustomValues implements JsonSerializable
{
    /** @var array */
    protected array $values = [];

    /**
     * @param array|null $values
     *
     * @return static
     */
    public static function create(?array $values = null): static
    {
        return new static($values);
    }

    /**
     * @param string|null $string $string
     *
     * @return static
     */
    public static function fromJson(?string $string): static
    {
        return new static((array) JsonString::decodeOptional($string));
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
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
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        return $default;
    }

    #[ReturnTypeWillChange]
    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) $this->values;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @param array|null $values
     */
    protected function __construct(?array $values = null)
    {
        if ($values === null) {
            return;
        }

        $this->values = $values;
    }
}
