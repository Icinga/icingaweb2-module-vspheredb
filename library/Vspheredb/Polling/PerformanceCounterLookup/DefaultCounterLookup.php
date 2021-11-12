<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use gipfl\ZfDb\Adapter\Adapter;
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

    public function fetchTags(UuidInterface $vCenterUuid = null)
    {
        $result = [];
        $query = $this->prepareBaseQuery($vCenterUuid)->columns($this->getTagColumns());
        foreach ($this->db->fetchAll($query) as $row) {
            $result[$row->{$this->getObjectKey()} . '/' . $row->{$this->getInstanceKey()}] = (array) $row;
        }

        return $result;
    }

    public function fetchRequiredMetricInstances(UuidInterface $vCenterUuid = null)
    {
        return static::explodeInstances($this->db->fetchPairs($this->prepareInstancesQuery($vCenterUuid)));
    }

    abstract protected function prepareInstancesQuery(UuidInterface $vCenterUuid);

    protected static function explodeInstances($queryResult)
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

    /**
     * @param $property
     * @return RuntimeException
     */
    protected function missingPropertyError($property)
    {
        return new RuntimeException(sprintf(
            '$%s is required when extending %s, missing in %s',
            $property,
            __CLASS__,
            get_class($this)
        ));
    }
}
