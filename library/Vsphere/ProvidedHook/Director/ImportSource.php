<?php

namespace Icinga\Module\Vsphere\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vsphere\Api;

class ImportSource extends ImportSourceHook
{
    /** @var Api */
    protected $api;

    public function getName()
    {
        return 'VMware vSphere';
    }

    protected function api()
    {
        if ($this->api === null) {
            $this->api = new Api(
                $this->getSetting('host'),
                $this->getSetting('username'),
                $this->getSetting('password')
            );
        }

        return $this->api;
    }

    public function fetchData()
    {
        $api = $this->api();
        $api->login();
        $vms = $this->callOnManagedObject('fetchWithDefaults', $api);
        $api->logout();
        return $vms;
    }

    protected function getManagedObjectClass()
    {
        return 'Icinga\\Module\\Vsphere\\ManagedObject\\'
            . $this->getSetting('objectType');
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

    public function listColumns()
    {
        return $this->callOnManagedObject('getDefaultPropertySet');
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
        $form->addElement('select', 'objectType', array(
            'label' => $form->translate('Object Type'),
            'description' => $form->translate(
                'The managed vSphere object type this Import Source should fetch'
            ),
            'multiOptions' => $form->optionalEnum(array(
                'VirtualMachine' => 'VirtualMachine'
            )),
            'required' => true,
        ));

        $form->addElement('text', 'host', array(
            'label' => $form->translate('vCenter (or ESX) host'),
            'description' => $form->translate(
                'It is strongly suggested to access the API through your vCenter'
            ),
            'required' => true,
        ));
        $form->addElement('text', 'username', array(
            'label' => $form->translate('Username'),
            'required' => true,
        ));
        $form->addElement('password', 'password', array(
            'label' => $form->translate('Password'),
            'required' => true,
        ));
    }
}
