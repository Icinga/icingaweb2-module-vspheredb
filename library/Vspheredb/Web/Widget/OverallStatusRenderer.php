<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\Html;
use ipl\I18n\Translation;

class OverallStatusRenderer extends Html
{
    use Translation;

    public function __invoke($state)
    {
        if (is_object($state)) {
            if (isset($state->runtime_power_state)) {
                $powerState = $state->runtime_power_state;
            } else {
                $powerState = null;
            }
            $state = $state->overall_status;
        } else {
            $powerState = null;
        }

        if ($powerState === null || $powerState === 'poweredOn') {
            return Icon::create($state === 'green' ? 'ok' : 'warning-empty', [
                'title' => $this->getStatusDescription($state),
                'class' => [ 'state', $state ]
            ]);
        } else {
            $powerInfo = new PowerStateRenderer();

            return $powerInfo($powerState);
        }
    }

    /**
     * @param string $status
     *
     * @return string
     */
    protected function getStatusDescription(string $status): string
    {
        $descriptions = [
            'gray'   => $this->translate('Gray - status is unknown'),
            'green'  => $this->translate('Green - everything is fine'),
            'yellow' => $this->translate('Yellow - there are warnings'),
            'red'    => $this->translate('Red - there is a problem'),
        ];

        return $descriptions[$status];
    }
}
