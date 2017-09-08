<?php

namespace Icinga\Module\Vspheredb\PropertySet;

class PropertySet
{
    protected $type;

    protected $properties;

    public function __construct($type, $properties)
    {
        $this->type = $type;
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type'    => $this->type,
            'all'     => 0,
            'pathSet' => $this->properties
        ];
    }
}
