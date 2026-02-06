<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbObject as VspheredbDbObject;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\Exception\DuplicateKeyException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ManagedObject extends VspheredbDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'object';

    protected ?array $defaultProperties = [
        'uuid'           => null,
        'vcenter_uuid'   => null,
        'moref'          => null,
        'object_name'    => null,
        'object_type'    => null,
        'overall_status' => null,
        'level'          => null,
        'parent_uuid'    => null,
        'tags'           => null,
    ];

    /** @var ?ManagedObject */
    private ?ManagedObject $parent = null;

    /**
     * @param string $uuid
     * @param Db     $connection
     *
     * @return static
     *
     * @throws NotFoundError
     */
    public static function loadWithUuid(string $uuid, Db $connection): ManagedObject
    {
        if (strlen($uuid) === 16) {
            $uuid = Uuid::fromBytes($uuid);
        } else {
            $uuid = Uuid::fromString($uuid);
        }

        return static::load($uuid->getBytes(), $connection);
    }

    /**
     * @returns void
     *
     * @throws DuplicateKeyException
     */
    protected function beforeStore(): void
    {
        if (null !== $this->parent) {
            $this->parent->store();
            $this->set('parent_uuid', $this->parent->get('uuid'));
        }
        if ($this->get('tags') === null) {
            $this->set('tags', '[]');
        }

        $this->set('level', $this->calculateLevel());
    }

    /**
     * @return mixed
     */
    public function getBinaryUuid(): mixed
    {
        return $this->get('uuid');
    }

    /**
     * @param ManagedObject $object
     *
     * @return $this
     */
    public function setParent(ManagedObject $object): static
    {
        $this->parent = $object;
        // Hint: parent change hasn't been detected otherwise.
        // TODO: check whether change detection is still fine
        if ($object->hasBeenLoadedFromDb()) {
            $this->set('parent_uuid', $object->get('uuid'));
        } else {
            $this->set('parent_uuid', 'NOT YET, SETTING A TOO LONG STRING');
        }

        return $this;
    }

    /**
     * @param VCenter $vCenter
     *
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter): array
    {
        $dummy = new static();

        $db = $vCenter->getDb();
        return static::loadAll(
            $vCenter->getConnection(),
            $db
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', DbUtil::quoteBinaryCompat($vCenter->get('uuid'), $db)),
            $dummy->keyName
        );
    }

    /**
     * @return int
     */
    public function getNumericLevel(): int
    {
        $level = $this->get('level');
        if ($level === null) {
            throw new RuntimeException('Cannot read ManagedObject level before setting one');
        }

        return (int) $level;
    }

    /**
     * @return int
     */
    public function calculateLevel(): int
    {
        if ($this->parent === null) {
            return 0;
        } else {
            return $this->parent->calculateLevel() + 1;
        }
    }

    /**
     * @param string $column
     *
     * @return bool
     */
    protected function isBinaryColumn(string $column): bool
    {
        if ($column === 'uuid' || str_ends_with($column, '_uuid')) {
            return true;
        }

        return parent::isBinaryColumn($column);
    }
}
