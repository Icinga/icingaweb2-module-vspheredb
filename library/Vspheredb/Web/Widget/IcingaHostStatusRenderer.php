<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;

class IcingaHostStatusRenderer extends Html
{
    use TranslationHelper;

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
