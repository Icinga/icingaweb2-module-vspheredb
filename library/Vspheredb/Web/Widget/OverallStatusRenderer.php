<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;

class OverallStatusRenderer extends Html
{
    use TranslationHelper;

    public function __invoke($state)
    {
        if (is_object($state)) {
            $state = $state->overall_status;
        }

        return Icon::create($state === 'green' ? 'ok' : 'warning-empty', [
            'title' => $this->getStatusDescription($state),
            'class' => [ 'state', $state ]
        ]);
    }

    protected function getStatusDescription($status)
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
