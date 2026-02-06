<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;

class CheckRelatedLookup
{
    /** @var Db */
    protected Db $connection;

    /**
     * @param Db $connection
     */
    public function __construct(Db $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function listNonGreenObjects(string $type): array
    {
        $db = $this->connection->getDbAdapter();
        $select = $db->select()
            ->from('object', ['uuid', 'overall_status', 'object_name'])
            ->where('object_type = ?', $type)
            ->where('overall_status != ?', 'green')
            ->order("CASE overall_status WHEN 'gray' THEN 1 WHEN 'yellow' THEN 2 WHEN 'red' THEN 3 END DESC")
            ->order('object_name');

        $result = [];
        /** @var object{uuid: string, overall_status: string, object_name: string} $row */
        foreach ($db->fetchAll($select) as $row) {
            if (isset($result[$row->overall_status])) {
                $result[$row->overall_status][$row->uuid] = $row->object_name;
            } else {
                $result[$row->overall_status] = [$row->uuid => $row->object_name];
            }
        }

        return $result;
    }

    /**
     * @param string $type
     * @param array  $filter
     *
     * @return BaseDbObject
     *
     * @throws NotFoundError
     */
    public function findOneBy(string $type, array $filter): BaseDbObject
    {
        $result = $this->findBy($type, $filter);

        if (empty($result)) {
            throw new NotFoundError('No object found for given filter');
        }

        if (count($result) > 1) {
            throw new NotFoundError('More than one object found for given filter');
        }

        $class = static::getClassForType($type);
        $object = $class::create();
        $object->setConnection($this->connection)->setDbProperties($result[0]);

        return $object;
    }

    /**
     * @param string $type
     * @param array  $filter
     *
     * @return array
     */
    private function findBy(string $type, array $filter): array
    {
        $db = $this->connection->getDbAdapter();
        $class = static::getClassForType($type);
        $table = $class::create()->getTableName();
        $select = $db->select()->from($table);

        foreach ($filter as $key => $value) {
            if ($key === 'object_name') {
                $select->join(
                    'object',
                    $db->quoteInto("object.uuid = $table.uuid AND object.object_type = ?", $type),
                    []
                );
            }
            if ($value === null) {
                $select->where($key);
            } elseif (! str_contains($key, '?')) {
                $select->where("$key = ?", $value);
            } else {
                $select->where($key, $value);
            }
        }

        return $db->fetchAll($select);
    }

    /**
     * @param string $type
     *
     * @return class-string
     *
     * @throws InvalidArgumentException
     */
    private static function getClassForType(string $type): string
    {
        return match ($type) {
            'VirtualMachine' => VirtualMachine::class,
            'HostSystem'     => HostSystem::class,
            'Datastore'      => Datastore::class,
            default          => throw new InvalidArgumentException("'$type' is an unknown type")
        };
    }
}
