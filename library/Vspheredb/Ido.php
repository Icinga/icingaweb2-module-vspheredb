<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Data\Db\DbConnection;
use Icinga\Data\ResourceFactory;

class Ido
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected function __construct()
    {
    }

    public static function createByResourceName($name)
    {
        $self = new static();
        /** @var DbConnection $resource */
        $resource = ResourceFactory::create($name);
        $self->db = $resource->getDbAdapter();

        return $self;
    }

    public function isAvailable()
    {
        // TODO: check program state
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
        if (null === $hostname) {
            return false;
        }

        return $this->db->fetchOne(
            $this->db->select()->from('icinga_objects', [
                'host_name' => 'name1',
            ])->where('name1 = ? AND is_active = 1 AND objecttype_id = 1', $hostname)
        ) === $hostname;
    }

    public function getHostState($hostname)
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
                'output'          => "hs.output",
            ]
        )->join(
            ['hs' => 'icinga_hoststatus'],
            'o.object_id = hs.host_object_id',
            []
        )->where('name1 = ? AND is_active = 1 AND objecttype_id = 1', $hostname);

        return $this->db->fetchRow($query);
    }
}
