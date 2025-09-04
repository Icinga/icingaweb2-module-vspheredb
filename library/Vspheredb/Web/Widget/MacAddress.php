<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Imedge\Web\PublicWidget\MacAddress as MacAddressWidget;

class MacAddress
{
    public static function show($value)
    {
        if ($value === null) {
            return null;
        }

        if (class_exists(MacAddressWidget::class)) {
            return MacAddressWidget::show($value);
        }

        return $value;
    }

    public static function showBinary($value)
    {
        if ($value === null) {
            return null;
        }

        if (class_exists(MacAddressWidget::class)) {
            return MacAddressWidget::showBinary($value);
        }

        return $value;
    }
}
