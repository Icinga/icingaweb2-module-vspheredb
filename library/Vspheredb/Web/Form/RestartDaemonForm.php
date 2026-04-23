<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\I18n\Translation;
use React\EventLoop\LoopInterface;

use function React\Async\await;

class RestartDaemonForm extends InlineForm
{
    use Translation;

    /** @var RemoteClient */
    protected $client;

    /** @var LoopInterface */
    protected $loop;

    public function __construct(RemoteClient $client, LoopInterface $loop)
    {
        $this->client = $client;
        $this->loop = $loop;
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
        await($this->client->request('process.restart'));
    }
}
