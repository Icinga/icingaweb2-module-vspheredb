<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;

abstract class DefaultCounterLookup implements CounterLookup
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected Zend_Db_Adapter_Abstract $db;

    /** @var string[]|null */
    protected ?array $tagColumns = null;

    protected ?string $objectKey = null;

    protected ?string $instanceKey = null;

    protected ?string $staticInstanceKey = null;

    /**
     * @param Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    public function hasInstanceKey(): bool
    {
        return $this->instanceKey !== null;
    }

    public function fetchTags(?UuidInterface $vCenterUuid = null): array
    {
        $result = [];
        $query = $this->prepareBaseQuery($vCenterUuid)->columns($this->getTagColumns());
        $objectKey = $this->getObjectKey();
        if ($this->hasInstanceKey()) {
            $hasInstanceKey = true;
            $instanceKey = $this->getInstanceKey();
        } else {
            $hasInstanceKey = false;
            $instanceKey = $this->staticInstanceKey;
        }
        foreach ($this->db->fetchAll($query) as $row) {
            $this->convertResultRowUuidsToText($row);
            if ($hasInstanceKey) {
                $result[$row->$objectKey . '/' . $row->$instanceKey] = (array) $row;
            } elseif ($instanceKey === null) {
                $result[$row->$objectKey ?? ''] = (array) $row;
            } else {
                $result[$row->$objectKey . '/' . $instanceKey] = (array) $row;
            }
        }

        return $result;
    }

    public function fetchRequiredMetricInstances(?UuidInterface $vCenterUuid = null): array
    {
        if ($this->hasInstanceKey()) {
            return static::explodeInstances($this->db->fetchPairs($this->prepareInstancesQuery($vCenterUuid)));
        } else {
            return $this->db->fetchPairs($this->prepareInstancesQuery($vCenterUuid));
        }
    }

    abstract protected function prepareBaseQuery(UuidInterface $vCenterUuid): Zend_Db_Select;

    abstract protected function prepareInstancesQuery(UuidInterface $vCenterUuid): Zend_Db_Select;

    protected static function explodeInstances($queryResult): array
    {
        $result = [];

        foreach ($queryResult as $key => $value) {
            $result[$key] = explode(',', $value);
        }

        return $result;
    }

    protected function getTagColumns(): array
    {
        if ($this->tagColumns === null) {
            throw $this->missingPropertyError('tagColumns');
        }

        return $this->tagColumns;
    }

    protected function getObjectKey(): string
    {
        if ($this->objectKey === null) {
            throw $this->missingPropertyError('objectKey');
        }

        return $this->objectKey;
    }

    protected function getInstanceKey(): string
    {
        if ($this->instanceKey === null) {
            throw $this->missingPropertyError('instanceKey');
        }

        return $this->instanceKey;
    }

    protected function convertResultRowUuidsToText($row): void
    {
        foreach (array_keys((array) $row) as $key) {
            if ($key === 'uuid' || str_ends_with($key, '_uuid')) {
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
