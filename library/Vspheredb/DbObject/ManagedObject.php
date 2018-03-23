<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Director\Data\Db\DbObject as DirectorDbObject;

class ManagedObject extends DirectorDbObject
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
