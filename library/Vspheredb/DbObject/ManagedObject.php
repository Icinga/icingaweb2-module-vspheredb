<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db\DbObject as VspheredbDbObject;

class ManagedObject extends VspheredbDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'object';

    protected $defaultProperties = [
        'uuid'           => null,
        'vcenter_uuid'   => null,
        'moref'          => null,
        'object_name'    => null,
        'object_type'    => null,
        'overall_status' => null,
        'level'          => null,
        'parent_uuid'    => null,
    ];

    /** @var ManagedObject */
    private $parent;

    /**
     * @throws \Icinga\Module\Vspheredb\Exception\DuplicateKeyException
     */
    protected function beforeStore()
    {
        if (null !== $this->parent) {
            $this->parent->store();
            $this->set('parent_uuid', $this->parent->get('uuid'));
        }

        $this->set('level', $this->calculateLevel());
    }

    public function setParent(ManagedObject $object)
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
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter)
    {
        $dummy = new static();

        return static::loadAll(
            $vCenter->getConnection(),
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $vCenter->get('uuid')),
            $dummy->keyName
        );
    }

    public function calculateLevel()
    {
        if ($this->parent === null) {
            return 0;
        } else {
            return $this->parent->calculateLevel() + 1;
        }
    }
}
