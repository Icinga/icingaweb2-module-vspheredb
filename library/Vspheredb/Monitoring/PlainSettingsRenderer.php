<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use gipfl\Translation\StaticTranslator;
use Icinga\Module\Vspheredb\Monitoring\Rule\InheritedSettings;

class PlainSettingsRenderer
{
    public static function render(InheritedSettings $settings): string
    {
        $translator = StaticTranslator::get();
        $output = '';
        foreach ($settings->toArray() as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) { // Impossible
                $value = 'null';
            } elseif (is_string($value)) {
                $value = '"' . addcslashes($value, '"') . '"';
            }
            if ($from = $settings->getInheritedFromName($name)) {
                $value .= sprintf(' (%s)', sprintf($translator->translate('inherited from %s'), $from));
            }

            $output .= "$name = $value\n";
        }

        return $output;
    }
}
