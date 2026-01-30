<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;

class GuestToolsStatusRenderer extends Html
{
    use TranslationHelper;

    public function __invoke($state): Icon
    {
        if (is_object($state)) {
            $state = $state->guest_tools_status;
        }

        return match ($state) {
            'toolsNotInstalled' => Icon::create('block', [
                'class' => 'red',
                'title' => $this->translate('Guest Tools are NOT installed'),
            ]),
            'toolsNotRunning'   => Icon::create('warning-empty', [
                'class' => 'red',
                'title' => $this->translate('Guest Tools are NOT running'),
            ]),
            'toolsOld'          => Icon::create('thumbs-down', [
                'class' => 'yellow',
                'title' => $this->translate('Guest Tools are outdated'),
            ]),
            'toolsOk'           => Icon::create('ok', [
                'class' => 'green',
                'title' => $this->translate('Guest Tools are up to date and running'),
            ]),
            default             => Icon::create('help', [
                'class' => 'gray',
                'title' => $this->translate('Guest Tools status is now known'),
            ])
        };
    }
}
