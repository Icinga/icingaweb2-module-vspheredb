<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Web\Form\Element\VCenterSelection;
use Zend_Db_Adapter_Abstract;

class FilterVCenterForm extends Form
{
    use TranslationHelper;

    protected $method = 'GET';

    /** @var Auth */
    protected Auth $auth;

    /** @var Db */
    protected Db $connection;

    /** @var Zend_Db_Adapter_Abstract  */
    protected Zend_Db_Adapter_Abstract $db;

    protected $useFormName = false;

    protected $defaultDecoratorClass = null;

    protected $useCsrf = false;

    protected bool $allowAllVCenters = false;

    public function __construct(Db $connection, Auth $auth)
    {
        $this->db = $connection->getDbAdapter();
        $this->auth = $auth;
        $this->connection = $connection;
    }

    public function allowAllVCenters(bool $allow = true): static
    {
        $this->allowAllVCenters = $allow;

        return $this;
    }

    public function getHexUuid(): string
    {
        return $this->getElement('vcenter')->getValue();
    }

    protected function assemble(): void
    {
        $this->addElement(new VCenterSelection($this->connection, $this->auth, !$this->allowAllVCenters));
    }
}
