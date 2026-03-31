<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\Html;
use ipl\I18n\Translation;

class IcingaHostStatusRenderer extends Html
{
    use Translation;

    public function __invoke($state)
    {
        if (is_object($state)) {
            $state = $state->overall_status;
        }

        return Icon::create('eye', [
            'title' => $this->getStatusDescription($state),
            'class' => [ 'state', $state ]
        ]);
    }

    protected function getStatusDescription($status)
    {
        $descriptions = [
            'UP'   => $this->translate('This system is up'),
            'DOWN'        => $this->translate('This system is down'),
            'UNREACHABLE' => $this->translate('Unreachable - another device might be responsible for this outage'),
            'PENDING'     => $this->translate('Pending - this host has never been checked'),
        ];

        return $descriptions[$status];
    }
}
