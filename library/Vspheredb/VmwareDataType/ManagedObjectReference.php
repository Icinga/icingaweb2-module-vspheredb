<?php

namespace Icinga\Module\Vspheredb\VmwareDataType;

use JsonSerializable;

class ManagedObjectReference implements JsonSerializable
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

    public function __toString()
    {
        if (true) {
            throw new \Exception('Moref to string!');
        }

        return $this->_;
    }

    public function jsonSerialize()
    {
        return (object) [
            '_'    => $this->_,
            'type' => $this->type,
        ];
    }
}
