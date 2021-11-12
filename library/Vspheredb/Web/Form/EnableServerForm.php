<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;

class EnableServerForm extends InlineForm
{
    use TranslationHelper;

    protected $serverId;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct($serverId, $db)
    {
        $this->serverId = $serverId;
        $this->db = $db;
    }

    public function getUniqueFormName()
    {
        return parent::getUniqueFormName() . '-' . $this->serverId;
    }

    protected function assemble()
    {
        (new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Enable')),
            NextConfirmCancel::buttonConfirm($this->translate('Really enable')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'))
        ))->addToForm($this);
    }

    public function onSuccess()
    {
        $this->db->update('vcenter_server', [
            'enabled' => 'y'
        ], $this->db->quoteInto('id = ?', $this->serverId));
    }
}
