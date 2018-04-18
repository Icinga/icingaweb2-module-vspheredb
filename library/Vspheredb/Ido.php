<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Application\Icinga;
use Icinga\Data\Db\DbConnection;
use Icinga\Module\Monitoring\Backend\Ido\IdoBackend;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

class Ido
{
    /** @var MonitoringBackend */
    protected $backend;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct()
    {
        $app = Icinga::app();
        $modules = $app->getModuleManager();
        if (!$modules->hasLoaded('monitoring') && $app->isCli()) {
            $app->getModuleManager()->loadEnabledModules();
        }

        if ($modules->hasLoaded('monitoring')) {
            $this->backend = MonitoringBackend::instance();
            if ($this->backend instanceof IdoBackend) {
                /** @var DbConnection $resource */
                $resource = $this->backend->getResource();
                $this->db = $resource->getDbAdapter();
            }
        }
    }

    public function isAvailable()
    {
        return $this->db !== null;
    }

    public function getAllHostStates()
    {
        $query = $this->db->select()->from(
            ['o' => 'icinga_objects'],
            [
                'host_name'       => 'o.name1',
                'current_state'   => "(CASE WHEN has_been_checked = 1 THEN"
                    . " CASE hs.current_state WHEN 0 THEN 'UP' WHEN 1 THEN 'DOWN'"
                    . " WHEN 2 THEN 'UNREACHABLE' END"
                    . " ELSE 'PENDING' END)",
                'is_in_downtime'  => "(CASE WHEN hs.scheduled_downtime_depth > 0 THEN 'y' ELSE 'n' END)",
                'is_acknowledged' => "(CASE WHEN hs.problem_has_been_acknowledged = 1 THEN 'y' ELSE 'n' END)",
            ]
        )->join(
            ['hs' => 'icinga_hoststatus'],
            'o.object_id = hs.host_object_id',
            []
        )->where('o.is_active = 1');

        return $this->db->fetchAll($query);
    }

    public function hasHost($hostname)
    {
        return $this->backend->select()->from('hostStatus', array(
                'hostname' => 'host_name',
            ))->where('host_name', $hostname)->fetchOne() === $hostname;
    }

    public function getHostState($hostname)
    {
        $hostStates = array(
            '0'  => 'up',
            '1'  => 'down',
            '2'  => 'unreachable',
            '99' => 'pending',
        );

        $query = $this->backend->select()->from('hostStatus', array(
            'hostname'     => 'host_name',
            'state'        => 'host_state',
            'problem'      => 'host_problem',
            'acknowledged' => 'host_acknowledged',
            'in_downtime'  => 'host_in_downtime',
            'output'       => 'host_output',
        ))->where('host_name', $hostname);

        $res = $query->fetchRow();
        if ($res === false) {
            $res = (object) array(
                'hostname'     => $hostname,
                'state'        => '99',
                'problem'      => '0',
                'acknowledged' => '0',
                'in_downtime'  => '0',
                'output'       => null,
            );
        }

        $res->state = $hostStates[$res->state];

        return $res;
    }
}
