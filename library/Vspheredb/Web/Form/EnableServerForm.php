<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Icinga\Module\Vspheredb\Db;

class EnableServerForm extends InlineForm
{
    protected $serverId;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct($serverId, $db)
    {
        $this->serverId = $serverId;
        $this->db = $db;
        parent::__construct();
    }

    public function getUniqueFormName()
    {
        return parent::getUniqueFormName() . '-' . $this->serverId;
    }

    protected function assemble()
    {
        $this->provideAction($this->translate('Enable'), $this->translate('Really enable'));
    }

    public function onSuccess()
    {
        $this->db->update('vcenter_server', [
            'enabled' => 'y'
        ], $this->db->quoteInto('id = ?', $this->serverId));
    }
}
