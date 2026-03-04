<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use React\EventLoop\LoopInterface;

use function React\Async\await;

class RestartDaemonForm extends InlineForm
{
    use TranslationHelper;

    /** @var RemoteClient */
    protected RemoteClient $client;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    public function __construct(RemoteClient $client, LoopInterface $loop)
    {
        $this->client = $client;
        $this->loop = $loop;
    }

    protected function assemble(): void
    {
        (new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Restart'), [
                'title' => $this->translate('Click to restart the vSphereDB background daemon')
            ]),
            NextConfirmCancel::buttonConfirm($this->translate('Yes, please restart')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'))
        ))->addToForm($this);
    }

    protected function onSuccess(): void
    {
        await($this->client->request('process.restart'));
    }
}
