<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use gipfl\ZfDb\Adapter\Adapter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

abstract class DefaultCounterLookup implements CounterLookup
{
    /**
     * @var Adapter|\Zend_Db_Adapter_Abstract
     */
    protected $db;

    protected $tagColumns;

    protected $objectKey;

    protected $instanceKey;

    /**
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function fetchTags(UuidInterface $vCenterUuid = null): array
    {
        $result = [];
        $query = $this->prepareBaseQuery($vCenterUuid)->columns($this->getTagColumns());
        foreach ($this->db->fetchAll($query) as $row) {
            $this->convertResultRowUuidsToText($row);
            $result[$row->{$this->getObjectKey()} . '/' . $row->{$this->getInstanceKey()}] = (array) $row;
        }

        return $result;
    }

    public function fetchRequiredMetricInstances(UuidInterface $vCenterUuid = null): array
    {
        return static::explodeInstances($this->db->fetchPairs($this->prepareInstancesQuery($vCenterUuid)));
    }

    abstract protected function prepareBaseQuery(UuidInterface $vCenterUuid);

    abstract protected function prepareInstancesQuery(UuidInterface $vCenterUuid);

    protected static function explodeInstances($queryResult): array
    {
        $result = [];

        foreach ($queryResult as $key => $value) {
            $result[$key] = preg_split('/,/', $value);
        }

        return $result;
    }

    protected function getTagColumns()
    {
        if ($this->tagColumns === null) {
            throw $this->missingPropertyError('tagColumns');
        }

        return $this->tagColumns;
    }

    protected function getObjectKey()
    {
        if ($this->objectKey === null) {
            throw $this->missingPropertyError('objectKey');
        }

        return $this->objectKey;
    }

    protected function getInstanceKey()
    {
        if ($this->instanceKey === null) {
            throw $this->missingPropertyError('instanceKey');
        }

        return $this->instanceKey;
    }

    protected function convertResultRowUuidsToText($row)
    {
        foreach (array_keys((array) $row) as $key) {
            if ($key === 'uuid' || substr($key, -5) === '_uuid') {
                if (strlen($row->$key) === 16) {
                    $row->$key = Uuid::fromBytes($row->$key)->toString();
                }
            }
        }
    }

    protected function missingPropertyError(string $property): RuntimeException
    {
        return new RuntimeException(sprintf(
            '$%s is required when extending %s, missing in %s',
            $property,
            __CLASS__,
            get_class($this)
        ));
    }
}
