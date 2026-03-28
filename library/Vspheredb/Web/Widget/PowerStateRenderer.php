<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\Html;
use ipl\I18n\Translation;

class PowerStateRenderer extends Html
{
    use Translation;

    public function __invoke($state)
    {
        if (is_object($state)) {
            $state = $state->runtime_power_state;
        }
        return Icon::create('off', [
            'title' => $this->getPowerStateDescription($state),
            'class' => [ 'state', $state ]
        ]);
    }

    public function getPowerStateDescription($state)
    {
        $descriptions = [
            'poweredOn'  => $this->translate('Powered on'),
            'poweredOff' => $this->translate('Powered off'),
            'suspended'  => $this->translate('Suspended'),
            'standby'    => $this->translate('Standby'),
            'unknown'    => $this->translate('Power state is unknown (disconnected?)'),
        ];

        if (! array_key_exists($state, $descriptions)) {
            var_dump($state);
            return 'nono';
        }
        return $descriptions[$state];
    }
}
