<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Vspheredb\Ido;
use RuntimeException;
use Zend_Db_Adapter_Abstract;

class MonitoringConnection extends BaseDbObject
{
    protected string|array|null $keyName = 'id';

    protected ?string $table = 'monitoring_connection';

    protected ?array $defaultProperties = [
        'id'                          => null,
        'vcenter_uuid'                => null,
        'priority'                    => null,
        'source_type'                 => null,
        'source_resource_name'        => null,
        'host_property'               => null,
        'monitoring_host_property'    => null,
        'vm_property'                 => null,
        'monitoring_vm_host_property' => null
    ];

    /** @var ?Ido */
    protected ?Ido $monitoring = null;

    /**
     * @param VCenter $vCenter
     *
     * @return Ido|null
     *
     * @throws NotFoundError
     */
    public static function eventuallyLoadForVCenter(VCenter $vCenter): ?Ido
    {
        $db = $vCenter->getConnection();
        if (static::exists($vCenter->getUuid(), $db)) {
            return static::load($vCenter->getUuid(), $db)->getMonitoring();
        }

        return null;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getIdoDb(): Zend_Db_Adapter_Abstract
    {
        return $this->getMonitoringBackend()->getResource()->getDbAdapter();
    }

    /**
     * @return MonitoringBackend
     *
     * @throws RuntimeException
     */
    public function getMonitoringBackend(): MonitoringBackend
    {
        $this->assertIdo();

        try {
            return MonitoringBackend::instance($this->get('source_resource_name'));
        } catch (ConfigurationError $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return void
     *
     * @throws RuntimeException
     */
    protected function assertIdo(): void
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
    public function getMonitoring(): Ido
    {
        return $this->monitoring ??= Ido::createByResourceName($this->get('source_resource_name'));
    }
}
