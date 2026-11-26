<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\I18n\Translation;

class RestartDaemonForm extends InlineForm
{
    use Translation;

    protected RemoteClient $client;

    /**
     * Create a new RestartDaemonForm
     *
     * @param RemoteClient $client The controller's daemon RPC client
     */
    public function __construct(RemoteClient $client)
    {
        $this->client = $client;
    }

    protected function assemble()
    {
        (new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Restart'), [
                'title' => $this->translate('Click to restart the vSphereDB background daemon'),
            ]),
            NextConfirmCancel::buttonConfirm($this->translate('Yes, please restart')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'))
        ))->addToForm($this);
    }

    protected function onSuccess()
    {
        $this->client->requestSync('process.restart');
    }
}
