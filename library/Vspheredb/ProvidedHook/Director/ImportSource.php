<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Director;

use gipfl\Json\JsonString;
use Icinga\Module\Director\Forms\ImportSourceForm;
use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Ramsey\Uuid\Uuid;
use Zend_Db_Adapter_Abstract as ZfDb;
use function array_keys;
use function in_array;

/**
 * Class ImportSource
 *
 * This is where we provide an Import Source for the Icinga Director
 */
class ImportSource extends ImportSourceHook
{
    protected $hostColumns = [
        'object_name'             => 'o.object_name',
        'uuid'                    => 'o.uuid',
        'vcenter_name'            => 'vc.name',
        'sysinfo_vendor'          => 'h.sysinfo_vendor',
        'sysinfo_model'           => 'h.sysinfo_model',
        'sysinfo_uuid'            => 'h.sysinfo_uuid',
        'bios_version'            => 'h.bios_version',
        'bios_release_date'       => 'h.bios_release_date',
        'service_tag'             => 'h.service_tag',
        'hardware_cpu_cores'      => 'h.hardware_cpu_cores',
        'hardware_memory_size_mb' => 'h.hardware_memory_size_mb',
        'custom_values'           => 'h.custom_values',
        'tags'                    => 'o.tags',
    ];

    protected $vmColumns = [
        'object_name'         => 'o.object_name',
        'moref'               => 'o.moref',
        'uuid'                => 'o.uuid',
        'vcenter_name'        => 'vc.name',
        'guest_ip_address'    => 'vm.guest_ip_address',
        'hardware_numcpu'     => 'vm.hardware_numcpu',
        'hardware_memorymb'   => 'vm.hardware_memorymb',
        'guest_id'            => 'vm.guest_id',
        'bios_uuid'           => 'vm.bios_uuid',
        'guest_full_name'     => 'vm.guest_full_name',
        'guest_host_name'     => 'vm.guest_host_name',
        'runtime_power_state' => 'vm.runtime_power_state',
        'template'            => 'vm.template',
        'custom_values'       => 'vm.custom_values',
        'tags'                => 'o.tags',
    ];

    protected $computeResourceColumns = [
        'object_name'              => 'o.object_name',
        'object_type'              => 'o.object_type',
        'uuid'                     => 'o.uuid',
        'vcenter_name'             => 'vc.name',
        'effective_cpu_mhz'        => 'cr.effective_cpu_mhz',
        'effective_memory_size_mb' => 'cr.effective_memory_size_mb',
        'cpu_cores'                => 'cr.cpu_cores',
        'cpu_threads'              => 'cr.cpu_threads',
        'effective_hosts'          => 'cr.effective_hosts',
        'hosts'                    => 'cr.hosts',
        'total_cpu_mhz'            => 'cr.total_cpu_mhz',
        'total_memory_size_mb'     => 'cr.total_memory_size_mb',
        'tags'                     => 'o.tags',
    ];

    protected $datastoreColumns = [
        'object_name'          => 'o.object_name',
        'uuid'                 => 'o.uuid',
        'vcenter_name'         => 'vc.name',
        'maintenance_mode'     => 'ds.maintenance_mode',
        'capacity'             => 'ds.capacity',
        'multiple_host_access' => 'ds.multiple_host_access',
        'tags'                 => 'o.tags',
    ];

    public function getName()
    {
        return 'VMware vSphereDB';
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        assert($form instanceof ImportSourceForm);
        $form->addElement('select', 'object_type', [
            'label' => mt('vspheredb', 'Object Type'),
            'multiOptions' => $form->optionalEnum([
                'host_system'      => mt('vspheredb', 'Host Systems'),
                'virtual_machine'  => mt('vspheredb', 'Virtual Machine'),
                'compute_resource' => mt('vspheredb', 'Compute Resource'),
                'datastore'        => mt('vspheredb', 'Datastore'),
            ]),
            'class'    => 'autosubmit',
            'required' => true
        ]);
        $form->addElement('select', 'vcenter_uuid', [
            'label' => mt('vspheredb', 'vCenter'),
            'multiOptions' => [
                null => mt('vspheredb', '- any -')
            ] + self::enumVCenters(),
        ]);
        $type = $form->getSentOrObjectSetting('object_type');
        if ($type === 'virtual_machine') {
            $form->addBoolean('skip_powered_off', [
                'label' => mt('vspheredb', 'Skip powered off VMs'),
                'value' => 'n',
            ]);
            $form->addBoolean('skip_templates', [
                'label' => mt('vspheredb', 'Skip Templates'),
                'value' => 'y',
            ]);
        }
    }

    protected static function enumVCenters(): array
    {
        $db = Db::newConfiguredInstance();
        $pairs = $db->fetchPairs(
            $db->select()->from(['vc' => 'vcenter'], [
                'uuid' => 'LOWER(HEX(vc.instance_uuid))',
                'name' => "vc.name || ' (' || REPLACE(vc.api_name, 'VMware ', '') || ')'",
            ])->order('vc.name')
        );
        $enum = [];
        foreach ($pairs as $uuid => $label) {
            $enum[Uuid::fromString($uuid)->toString()] = $label;
        }

        return $enum;
    }

    protected function eventuallyFilterVCenter($query)
    {
        $vCenterUuid = $this->getSetting('vcenter_uuid');
        if ($vCenterUuid !== null && strlen($vCenterUuid) > 0) {
            $vCenterUuid = Uuid::fromString($vCenterUuid)->getBytes();
            $query->where('o.vcenter_uuid = ?', $vCenterUuid);
        }

        return $query;
    }

    public function fetchData(): array
    {
        $connection = Db::newConfiguredInstance();
        $db = $connection->getDbAdapter();
        $objectType = $this->getSetting('object_type');
        switch ($objectType) {
            case 'host_system':
                $query = $this->prepareHostsQuery($db);
                break;
            case 'virtual_machine':
                $query = $this->prepareVmQuery($db);
                break;
            case 'compute_resource':
                $query = $this->prepareComputeResourceQuery($db);
                break;
            case 'datastore':
                $query = $this->prepareDatastoreQuery($db);
                break;
            default:
                return [];
        }
        $result = $db->fetchAll(
            $this->eventuallyFilterVCenter($this->joinVCenter($query))
        );

        if (empty($result)) {
            return [];
        }

        foreach ($result as $row) {
            $row->uuid = Uuid::fromBytes(DbUtil::binaryResult($row->uuid))->toString();
            if (in_array($objectType, ['host_system', 'virtual_machine'])) {
                if ($row->custom_values !== null) {
                    $row->custom_values = JsonString::decode($row->custom_values);
                }
            }
            if ($objectType === 'virtual_machine') {
                $row->template = $row->template === 'y';
            }
            $row->tags = JsonString::decode($row->tags);
        }

        return $result;
    }

    protected function prepareVmQuery(ZfDb $db)
    {
        $query = $db->select()->from(['o' => 'object'], $this->vmColumns)->join(
            ['vm' => 'virtual_machine'],
            'o.uuid = vm.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
        if ($this->getSetting('skip_templates', 'y') === 'y') {
            $query->where('template = ?', 'n');
        }
        if ($this->getSetting('skip_powered_off', 'n') === 'y') {
            $query->where('runtime_power_state != ?', 'poweredOff');
        }

        return $query;
    }

    protected function prepareHostsQuery(ZfDb $db)
    {
        return $db->select()->from(['o' => 'object'], $this->hostColumns)->join(
            ['h' => 'host_system'],
            'o.uuid = h.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
    }

    protected function prepareComputeResourceQuery(ZfDb $db)
    {
        return $db->select()->from(['o' => 'object'], $this->computeResourceColumns)->join(
            ['cr' => 'compute_resource'],
            'o.uuid = cr.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
    }

    protected function prepareDatastoreQuery(ZfDb $db)
    {
        return $db->select()->from(['o' => 'object'], $this->datastoreColumns)->join(
            ['ds' => 'datastore'],
            'o.uuid = ds.uuid',
            []
        )->order('o.object_name')->order('o.uuid');
    }

    protected function joinVCenter($query)
    {
        return $query->join(
            ['vc' => 'vcenter'],
            'vc.instance_uuid = o.vcenter_uuid',
            []
        );
    }

    public function listColumns(): array
    {
        switch ($this->getSetting('object_type')) {
            case 'host_system':
                return array_keys($this->hostColumns);
            case 'virtual_machine':
                return array_keys($this->vmColumns);
            case 'compute_resource':
                return array_keys($this->computeResourceColumns);
            case 'datastore':
                return array_keys($this->datastoreColumns);
            default:
                return [];
        }

        // Alternative: return $this->callOnManagedObject('getDefaultPropertySet');
    }

    protected function getManagedObjectClass(): string
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
    public static function getDefaultKeyColumnName(): ?string
    {
        return 'object_name';
    }
}
