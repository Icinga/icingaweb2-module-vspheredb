<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use Icinga\Exception\ProgrammingError;
use Zend_Db_Adapter_Abstract;

class ServerActionForm extends InlineForm
{
    use TranslationHelper;

    /** @var ?string Set to either 'enable' or 'disable' */
    protected ?string $serverAction = null;

    /** @var int */
    protected int $serverId;

    /** @var Zend_Db_Adapter_Abstract */
    protected Zend_Db_Adapter_Abstract $db;

    public function __construct(int $serverId, Zend_Db_Adapter_Abstract $db)
    {
        $this->serverId = $serverId;
        $this->db = $db;
    }

    public function getUniqueFormName(): string
    {
        return parent::getUniqueFormName() . '-' . $this->serverId;
    }

    protected function assemble(): void
    {
        (new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate(ucfirst($this->serverAction))),
            NextConfirmCancel::buttonConfirm($this->translate('Really ' . $this->serverAction)),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'))
        ))->addToForm($this);
    }

    protected function onSuccess(): void
    {
        $enabled = match ($this->serverAction) {
            'enable'  => 'y',
            'disable' => 'n',
            default   => throw new ProgrammingError('Invalid server action provided: %s', $this->serverAction)
        };
        $this->db->update('vcenter_server', ['enabled' => $enabled], $this->db->quoteInto('id = ?', $this->serverId));
    }
}
