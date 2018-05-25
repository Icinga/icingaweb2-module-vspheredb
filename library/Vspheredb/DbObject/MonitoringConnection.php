<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Ido;

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
     * @throws \Icinga\Exception\IcingaException
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

    /**
     * @return Ido
     * @throws \Icinga\Exception\IcingaException
     */
    public function getMonitoring()
    {
        if ($this->monitoring === null) {
            $this->monitoring = Ido::createByResourceName($this->get('source_resource_name'));
        }

        return $this->monitoring;
    }
}
