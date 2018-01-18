<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vspheredb\Api;

/**
 * Class ImportSource
 *
 * This is where we provide an Import Source for the Icinga Director
 */
class ImportSource extends ImportSourceHook
{
    /** @var Api */
    protected $api;

    public function getName()
    {
        return 'VMware vSphereDB';
    }

    public function fetchData()
    {
        return [];
    }

    public function listColumns()
    {
        return $this->callOnManagedObject('getDefaultPropertySet');
    }

    protected function getManagedObjectClass()
    {
        return 'Icinga\\Module\\Vspheredb\\DbObject\\'
            . $this->getSetting('object_type');
    }

    protected function callOnManagedObject($method)
    {
        $params = func_get_args();
        array_shift($params);

        return call_user_func_array(array(
            $this->getManagedObjectClass(),
            $method
        ), $params);
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultKeyColumnName()
    {
        return 'name';
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
    }
}
