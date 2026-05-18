<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonEncodeException;
use gipfl\Json\JsonString;

trait CustomValueSupport
{
    /**
     * @param mixed $value
     *
     * @return void
     *
     * @throws JsonEncodeException
     */
    protected function setCustomValues(mixed $value): void
    {
        $this->set('custom_values', $value === null ? null : JsonString::encode($value));
    }


    /**
     * @return CustomValues
     */
    public function customValues(): CustomValues
    {
        return CustomValues::fromJson($this->get('custom_values'));
    }
}
