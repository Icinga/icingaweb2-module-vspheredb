<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Form\Element\VCenterSelection;

class FilterVCenterForm extends Form
{
    use TranslationHelper;

    /** @var Auth */
    protected $auth;

    /** @var Db */
    protected $connection;

    /** @var \Zend_Db_Adapter_Abstract  */
    protected $db;

    protected $useFormName = false;
    protected $defaultDecoratorClass = null;
    protected $useCsrf = false;
    protected $allowAllVCenters = false;

    public function __construct(Db $connection, Auth $auth)
    {
        $this->db = $connection->getDbAdapter();
        $this->setMethod('GET');
        $this->auth = $auth;
        $this->connection = $connection;
    }

    public function allowAllVCenters($allow = true): self
    {
        $this->allowAllVCenters = $allow;
        return $this;
    }

    public function getHexUuid()
    {
        return $this->getElement('vcenter')->getValue();
    }

    protected function assemble()
    {
        $this->addElement(new VCenterSelection($this->connection, $this->auth, !$this->allowAllVCenters));
    }
}
