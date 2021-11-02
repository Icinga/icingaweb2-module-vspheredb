<?php

namespace Icinga\Module\Vspheredb\DbObject;

use gipfl\Json\JsonString;

trait CustomValueSupport
{
    /**
     * @param $value
     * @throws \gipfl\Json\JsonEncodeException
     */
    protected function setCustomValues($value)
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
    public function customValues()
    {
        return CustomValues::fromJson($this->get('custom_values'));
    }
}
