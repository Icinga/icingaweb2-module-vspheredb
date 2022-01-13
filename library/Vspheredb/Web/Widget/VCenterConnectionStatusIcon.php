<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\StaticTranslator;
use Icinga\Module\Vspheredb\Polling\ApiConnection;

class VCenterConnectionStatusIcon
{
    /**
     * @param string $state
     * @return Icon
     */
    public static function create($state, $label)
    {
        $t = StaticTranslator::get();
        switch ($state) {
            case 'unknown':
                return Icon::create('help', [
                    'class' => 'unknown',
                    'title' => sprintf(
                        $t->translate('Connections to %s have been enabled, but none is currently active'),
                        $label
                    )
                ]);
            case 'disabled':
                return Icon::create('cancel', [
                    'title' => sprintf(
                        $t->translate('Connections to %s have been disabled'),
                        $label
                    )
                ]);
            case ApiConnection::STATE_CONNECTED:
                return Icon::create('ok', [
                    'class' => 'green',
                    'title' => sprintf(
                        $t->translate('API connection with %s is fine'),
                        $label
                    ),
                ]);
            case ApiConnection::STATE_LOGIN:
                return Icon::create('spinner', [
                    'class' => 'yellow',
                    'title' => sprintf(
                        $t->translate('Trying to log in to %s'),
                        $label
                    )
                ]);
            case ApiConnection::STATE_INIT:
                return Icon::create('spinner', [
                    'class' => 'yellow',
                    'title' => sprintf(
                        $t->translate('Initializing API connection with %s'),
                        $label
                    )
                ]);
            case ApiConnection::STATE_FAILING:
                return Icon::create('warning-empty', [
                    'class' => 'red',
                    'title' => sprintf(
                        $t->translate('API connection with %s is failing'),
                        $label
                    )
                ]);
            case ApiConnection::STATE_STOPPING:
                return Icon::create('cancel', [
                    'class' => 'yellow',
                    'title' => sprintf(
                        $t->translate('Stopping API connection with %s'),
                        $label
                    )
                ]);
        }

        return Icon::create('warning-empty', [
            'class' => 'warning',
            'title' => $t->translate('There is no configured server for this vCenter')
        ]);
    }
}
