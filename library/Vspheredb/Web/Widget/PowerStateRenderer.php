<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;

class PowerStateRenderer extends Html
{
    use TranslationHelper;

    public function __invoke($state)
    {
        if (is_object($state)) {
            $state = $state->runtime_power_state;
        }

        return Icon::create('off', [
            'title' => $this->getPowerStateDescription($state),
            'class' => ['state', $state]
        ]);
    }

    /**
     * @param string $state
     *
     * @return string
     */
    public function getPowerStateDescription(string $state): string
    {
        $result = match ($state) {
            'poweredOn'  => $this->translate('Powered on'),
            'poweredOff' => $this->translate('Powered off'),
            'suspended'  => $this->translate('Suspended'),
            'standby'    => $this->translate('Standby'),
            'unknown'    => $this->translate('Power state is unknown (disconnected?)'),
            default      => null
        };

        if ($result === null) {
            var_dump($state);
            return 'nono';
        }

        return $result;
    }
}
