<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Zend_Db_Adapter_Abstract as ZfDb;

/**
 * Class ImportSource
 *
 * This is where we provide an Import Source for the Icinga Director
 */
class ImportSource extends ImportSourceHook
{
    /** @var Api */
    protected $api;

    protected $hostColumns = [
        'object_name'             => 'o.object_name',
        'sysinfo_vendor'          => 'h.sysinfo_vendor',
        'sysinfo_model'           => 'h.sysinfo_model',
        'bios_version'            => 'h.bios_version',
        'bios_release_date'       => 'h.bios_release_date',
        'service_tag'             => 'h.service_tag',
        'hardware_cpu_cores'      => 'h.hardware_cpu_cores',
        'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
    ];

    protected $vmColumns = [
        'object_name'       => 'o.object_name',
        'guest_ip_address'  => 'vm.guest_ip_address',
        'hardware_numcpu'   => 'vm.hardware_numcpu',
        'hardware_memorymb' => 'vm.hardware_memorymb',
        'guest_id'          => 'vm.guest_id',
        'guest_full_name'   => 'vm.guest_full_name',
        'guest_host_name'   => 'vm.guest_host_name',
    ];

    public function getName()
    {
        return 'VMware vSphereDB';
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        $form->addElement('select', 'object_type', [
            'label' => $form->translate('Object Type'),
            'multiOptions' => $form->optionalEnum([
                'host_system'     => $form->translate('Host Systems'),
                'virtual_machine' => $form->translate('Virtual Machine'),
            ]),
            'required' => true
        ]);
    }

    public function fetchData()
    {
        $connection = Db::newConfiguredInstance();
        $db = $connection->getDbAdapter();
        switch ($this->getSetting('object_type')) {
            case 'host_system':
                return $db->fetchAll($this->prepareHostsQuery($db));
            case 'virtual_machine':
                return $db->fetchAll($this->prepareVmQuery($db));
            default:
                return [];
        }
    }

    protected function prepareVmQuery(ZfDb $db)
    {
        return $db->select()->from(['o' => 'object'], $this->vmColumns)->join(
            ['vm' => 'virtual_machine'],
            'o.uuid = vm.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
    }

    protected function prepareHostsQuery(ZfDb $db)
    {
        return $db->select()->from(['o' => 'object'], $this->hostColumns)->join(
            ['h' => 'host_system'],
            'o.uuid = h.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
    }

    public function listColumns()
    {
        switch ($this->getSetting('object_type')) {
            case 'host_system':
                return \array_keys($this->hostColumns);
            case 'virtual_machine':
                return \array_keys($this->vmColumns);
            default:
                return [];
        }

        // Alternative: return $this->callOnManagedObject('getDefaultPropertySet');
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
        return 'object_name';
    }
}
