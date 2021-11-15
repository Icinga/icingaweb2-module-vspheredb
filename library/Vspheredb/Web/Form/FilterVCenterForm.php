<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Form\Element\VCenterSelection;

class FilterVCenterForm extends Form
{
    use TranslationHelper;

    /** @var \Zend_Db_Adapter_Abstract  */
    protected $db;

    protected $useFormName = false;

    protected $useCsrf = false;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->setMethod('GET');
    }

    public function hasDefaultElementDecorator()
    {
        return false;
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
