<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\Html;
use ipl\I18n\Translation;

class GuestToolsStatusRenderer extends Html
{
    use Translation;

    public function __invoke($state)
    {
        if (is_object($state)) {
            $state = $state->guest_tools_status;
        }
        switch ($state) {
            case 'toolsNotInstalled':
                return Icon::create('block', [
                    'class' => 'red',
                    'title' => $this->translate('Guest Tools are NOT installed'),
                ]);
            case 'toolsNotRunning':
                return Icon::create('warning-empty', [
                    'class' => 'red',
                    'title' => $this->translate('Guest Tools are NOT running'),
                ]);
            case 'toolsOld':
                return Icon::create('thumbs-down', [
                    'class' => 'yellow',
                    'title' => $this->translate('Guest Tools are outdated'),
                ]);
            case 'toolsOk':
                return Icon::create('ok', [
                    'class' => 'green',
                    'title' => $this->translate('Guest Tools are up to date and running'),
                ]);
            case null:
            default:
                return Icon::create('help', [
                    'class' => 'gray',
                    'title' => $this->translate('Guest Tools status is now known'),
                ]);
        }
    }
}
