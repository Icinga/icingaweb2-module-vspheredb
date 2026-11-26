<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Form;

use Exception;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\Html\FormElement\SelectElement;
use ipl\I18n\Translation;
use Psr\Log\LogLevel;

class LogLevelForm extends InlineForm
{
    use Translation;

    protected RemoteClient $client;

    /** @var boolean */
    protected $talkedToSocket;

    /**
     * Create a new LogLevelForm
     *
     * @param RemoteClient $client The controller's daemon RPC client
     */
    public function __construct(RemoteClient $client)
    {
        $this->client = $client;
    }

    public function talkedToSocket()
    {
        return $this->talkedToSocket;
    }

    protected function assemble()
    {
        try {
            $currentLevel = $this->client->requestSync('logger.getLogLevel');
            $this->talkedToSocket = true;
        } catch (Exception $e) {
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
            'options'  => ['' => $this->translate('- please choose -')] + $this->listLogLevels(),
            'required' => true,
            'value'    => $currentLevel,
        ]));
        $toggle->addToForm($this);
    }

    protected function onSuccess()
    {
        $this->client->requestSync('logger.setLogLevel', ['level' => $this->getValue('log_level')]);
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
