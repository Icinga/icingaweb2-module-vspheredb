<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Form\Element\VCenterSelection;
use ipl\Html\Form;

class FilterVCenterForm extends Form
{
    use TranslationHelper;

    /** @var \Zend_Db_Adapter_Abstract  */
    protected $db;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->setMethod('GET');
    }

    public function getHexUuid()
    {
        return $this->getElement('vcenter')->getValue();
    }

    protected function assemble()
    {
        $this->addElement(new VCenterSelection($this->db, true));
    }
}
