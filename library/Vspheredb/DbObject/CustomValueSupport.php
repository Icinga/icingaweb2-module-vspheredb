<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;

trait CustomValueSupport
{
    /**
     * @param mixed $value
     *
     * @return void
     *
     * @throws \gipfl\Json\JsonEncodeException
     */
    protected function setCustomValues(mixed $value): void
    {
        if ($value === null) {
            $this->set('custom_values', null);
        } else {
            $this->set('custom_values', JsonString::encode($value));
        }
    }


    /**
     * @return CustomValues
     */
    public function customValues(): CustomValues
    {
        return CustomValues::fromJson($this->get('custom_values'));
    }
}
