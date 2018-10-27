<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Vspheredb\Ido;
use RuntimeException;

class MonitoringConnection extends BaseDbObject
{
    protected $keyName = 'vcenter_uuid';

    protected $table = 'monitoring_connection';

    protected $defaultProperties = [
        'vcenter_uuid'                => null,
        'priority'                    => null,
        'source_type'                 => null,
        'source_resource_name'        => null,
        'host_property'               => null,
        'monitoring_host_property'    => null,
        'vm_property'                 => null,
        'monitoring_vm_host_property' => null,
    ];

    protected $monitoring;

    /**
     * @param VCenter $vCenter
     * @return Ido|null
     * @throws \Icinga\Exception\NotFoundError
     */
    public static function eventuallyLoadForVCenter(VCenter $vCenter)
    {
        $db = $vCenter->getConnection();
        if (static::exists($vCenter->getUuid(), $db)) {
            return static::load(
                $vCenter->getUuid(),
                $db
            )->getMonitoring();
        } else {
            return null;
        }
    }

    public function getIdoDb()
    {
        /** @var \Icinga\Data\Db\DbConnection $resource */
        $resource = $this->getMonitoringBackend()->getResource();

        return $resource->getDbAdapter();
    }

    public function getMonitoringBackend()
    {
        $this->assertIdo();

        try {
            return MonitoringBackend::instance($this->get('source_resource_name'));
        } catch (ConfigurationError $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    protected function assertIdo()
    {
        if ($this->get('source_type') !== 'ido') {
            throw new RuntimeException(sprintf(
                'Only IDO connections are supported, got %s',
                $this->get('source_type')
            ));
        }
    }

    /**
     * @return Ido
     */
    public function getMonitoring()
    {
        if ($this->monitoring === null) {
            $this->monitoring = Ido::createByResourceName($this->get('source_resource_name'));
        }

        return $this->monitoring;
    }
}
