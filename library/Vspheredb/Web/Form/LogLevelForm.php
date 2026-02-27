<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\Html\FormElement\SelectElement;
use ipl\I18n\Translation;
use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;
use Zend_Db_Adapter_Abstract;

use function React\Async\await;

class LogLevelForm extends InlineForm
{
    use Translation;

    /** @var RemoteClient */
    protected $client;

    /** @var LoopInterface */
    protected $loop;

    /** @var Zend_Db_Adapter_Abstract */
    protected Zend_Db_Adapter_Abstract $db;

    /** @var boolean */
    protected $talkedToSocket;

    public function __construct(RemoteClient $client, LoopInterface $loop, Zend_Db_Adapter_Abstract $db)
    {
        $this->client = $client;
        $this->loop = $loop;
        $this->db = $db;
    }

    public function talkedToSocket()
    {
        return $this->talkedToSocket;
    }

    protected function assemble()
    {
        try {
            $currentLevel = await($this->client->request('logger.getLogLevel'));
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
            'options'  => ['' => $this->translate('- please choose -')] + $this->listLogLevels(),
            'required' => true,
            'value'    => $currentLevel,
        ]));
        $toggle->addToForm($this);
    }

    protected function onSuccess()
    {
        $logLevel = $this->getValue('log_level');
        await(
            $this->client->request('logger.setLogLevel', ['level' => $logLevel])
                ->then(function () use ($logLevel) {
                    $query = $this->db->select()
                        ->from('daemon_config', ['key'])
                        ->where('`key` = ?', 'log_level');
                    if ($this->db->query($query)->rowCount()) {
                        $this->db->update(
                            'daemon_config',
                            ['value' => $logLevel],
                            $this->db->quoteInto('`key` = ?', 'log_level')
                        );
                    } else {
                        $this->db->insert('daemon_config', ['`key`' => 'log_level', 'value' => $logLevel]);
                    }
                })
        );
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
