<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Json;

trait CustomValueSupport
{
    /**
     * @param $value
     * @throws \Icinga\Module\Vspheredb\Exception\JsonException
     */
    protected function setCustomValues($value)
    {
        if ($value === null) {
            $this->set('custom_values', null);
        } else {
            $this->set('custom_values', Json::encode($value));
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
