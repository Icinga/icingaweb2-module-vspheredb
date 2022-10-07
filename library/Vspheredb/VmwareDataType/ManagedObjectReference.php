<?php

namespace Icinga\Module\Vspheredb\VmwareDataType;

use gipfl\Json\JsonSerialization;

/**
 * #[AllowDynamicProperties]
 */
class ManagedObjectReference implements JsonSerialization
{
    /**
     * @codingStandardsIgnoreStart
     */
    public $_;
    // codingStandardsIgnoreEnd

    public $type;

    public function __construct($type, $moref)
    {
        $this->_ = $moref;
        $this->type = $type;
    }

    public function getLogName()
    {
        return $this->type . '[' . $this->_ . ']';
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            '_'    => $this->_,
            'type' => $this->type,
        ];
    }

    public static function fromSerialization($any)
    {
        return new static($any->type, $any->_);
    }
}
