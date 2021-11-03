<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Module\Vspheredb\Db;

class CheckRelatedLookup
{
    /** @var Db */
    protected $connection;

    public function __construct(Db $connection)
    {
        $this->connection = $connection;
    }

    public function listNonGreenObjects($type)
    {
        $db = $this->connection->getDbAdapter();
        $select = $db->select()
            ->from('object', ['uuid', 'overall_status', 'object_name'])
            ->where('object_type = ?', $type)
            ->where('overall_status != ?', 'green')
            ->order("CASE overall_status WHEN 'gray' THEN 1 WHEN 'yellow' THEN 2 WHEN 'red' THEN 3 END DESC")
            ->order('object_name');

        $result = [];
        foreach ($db->fetchAll($select) as $row) {
            $status = $row->overall_status;
            if (isset($result[$status])) {
                $result[$status][$row->uuid] = $row->object_name;
            } else {
                $result[$status] = [$row->uuid => $row->object_name];
            }
        }

        return $result;
    }
}
