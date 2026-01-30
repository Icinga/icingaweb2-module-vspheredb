<?php

namespace Icinga\Module\Vspheredb\VmwareDataType;

use gipfl\Json\JsonSerialization;

/**
 * #[AllowDynamicProperties]
 */
class ManagedObjectReference implements JsonSerialization
{
    public string $_; // phpcs:ignore

    public string $type;

    public function __construct(string $type, string $moref)
    {
        $this->_ = $moref;
        $this->type = $type;
    }

    public function getLogName(): string
    {
        return $this->type . '[' . $this->_ . ']';
    }

    #[\ReturnTypeWillChange]
    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) [
            '_'    => $this->_,
            'type' => $this->type,
        ];
    }

    public static function fromSerialization($any): static
    {
        return new static($any->type, $any->_);
    }
}
