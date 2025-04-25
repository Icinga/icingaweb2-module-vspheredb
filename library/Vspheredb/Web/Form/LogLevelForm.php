<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\Html\FormElement\SelectElement;
use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;

use function Clue\React\Block\await;

class LogLevelForm extends InlineForm
{
    use TranslationHelper;

    /** @var RemoteClient */
    protected $client;

    /** @var LoopInterface */
    protected $loop;

    /** @var boolean */
    protected $talkedToSocket;

    public function __construct(RemoteClient $client, LoopInterface $loop)
    {
        $this->client = $client;
        $this->loop = $loop;
    }

    public function talkedToSocket()
    {
        return $this->talkedToSocket;
    }

    protected function assemble()
    {
        try {
            $currentLevel = await($this->client->request('logger.getLogLevel'), $this->loop);
            $this->talkedToSocket = true;
        } catch (\Exception $e) {
            $this->talkedToSocket = false;
            return;
        }

        $toggle = new NextConfirmCancel(
            NextConfirmCancel::buttonNext($currentLevel ?: $this->translate('unspecified'), [
                'title' => $this->translate('Click to change the current Daemon Log Level')
            ]),
            NextConfirmCancel::buttonConfirm($this->translate('Set')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'))
        );
        $toggle->showWithConfirm(new SelectElement('log_level', [
            'options'  => [null => $this->translate('- please choose -')] + $this->listLogLevels(),
            'required' => true,
            'value'    => $currentLevel,
        ]));
        $toggle->addToForm($this);
    }

    protected function onSuccess()
    {
        await($this->client->request('logger.setLogLevel', [
            'level' => $this->getValue('log_level')
        ]), $this->loop);
    }

    protected function listLogLevels()
    {
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        return array_combine($levels, $levels);
    }
}
