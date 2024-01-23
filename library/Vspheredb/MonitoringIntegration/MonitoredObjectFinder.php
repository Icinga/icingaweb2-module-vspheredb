<?php

namespace Icinga\Module\Vspheredb\MonitoringIntegration;

use Icinga\Exception\InvalidPropertyException;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\CheckRelatedLookup;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class MonitoredObjectFinder
{
    /** @var Db */
    protected $db;
    /** @var CheckRelatedLookup */
    protected $lookup;
    /** @var array */
    protected $connections;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->lookup = new CheckRelatedLookup($this->db);
        $this->connections = $this->fetchConnections();
    }

    /**
     * @param MonitoredObject $object
     * @return HostSystem|VirtualMachine|null
     */
    public function find(MonitoredObject $object)
    {
        if (! $object instanceof Host) {
            return null;
        }

        foreach ($this->connections as $row) {
            try {
                $filter = $this->filterFromRow($object, 'host', $row);
                if ($filter && $host = $this->loadOptionalObject('HostSystem', $filter)) {
                    return $host;
                }
                $filter = $this->filterFromRow($object, 'vm', $row);
                if ($filter && $vm = $this->loadOptionalObject('VirtualMachine', $filter)) {
                    return $vm;
                }
            } catch (InvalidPropertyException $e) {
                // Shows problems when accessing MonitoredObjectProperties
                continue;
            }
        }

        return null;
    }

    /**
     * @param string $type
     * @param array $filter
     * @return HostSystem|VirtualMachine|null
     */
    protected function loadOptionalObject($type, $filter)
    {
        try {
            $object = $this->lookup->findOneBy($type, $filter);
            assert($object instanceof HostSystem || $object instanceof VirtualMachine);
            return $object;
        } catch (NotFoundError $e) {
            return null;
        }
    }

    /**
     * @param MonitoredObject $object
     * @param string $prefix
     * @param \stdClass $row
     * @return array|null
     */
    protected function filterFromRow(MonitoredObject $object, $prefix, $row)
    {
        $filter = [];
        $filterPrefix = $prefix === 'vm' ? 'virtual_machine' : 'host_system';
        if ($row->vcenter_uuid !== null) {
            $filter[$filterPrefix . '.vcenter_uuid'] = $row->vcenter_uuid;
        }
        $monPrefix = $prefix === 'vm' ? 'vm_host' : 'host';
        $monProperty = $row->{"monitoring_{$monPrefix}_property"};
        if ($monProperty === null) {
            return null;
        }

        if (preg_match('/^vars./', $monProperty)) {
            $varName = substr($monProperty, 5);
            $vars = $object->customvars;
            if (isset($vars[$varName])) {
                $value = $vars[$varName];
            } else {
                $value = null;
            }
        } else {
            $value =  $object->{$monProperty};
        }

        if ($value === null) {
            return null;
        }
        $filter[$row->{"{$prefix}_property"}] = $value;

        return $filter;
    }

    protected function fetchConnections()
    {
        return $this->db->getDbAdapter()->fetchAll(
            $this->db->getDbAdapter()->select()->from('monitoring_connection')
                ->where('source_type = ?', 'ido')
                ->order('priority DESC')
        );
    }
}
