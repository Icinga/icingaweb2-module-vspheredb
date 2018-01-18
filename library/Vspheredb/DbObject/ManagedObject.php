<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Director\Data\Db\DbObject as DirectorDbObject;

class ManagedObject extends DirectorDbObject
{
    protected $keyName = 'id';

    protected $table = 'object';

    protected $defaultProperties = [
        'object_checksum' => null,
        'vcenter_uuid'    => null,
        'id'              => null,
        'moref'           => null,
        'object_name'     => null,
        'object_type'     => null,
        'overall_status'  => null,
        'level'           => null,
        'parent_id'       => null,
    ];

    /** @var ManagedObject */
    private $parent;

    protected function beforeStore()
    {
        if (null !== $this->parent) {
            $this->parent->store();
            $this->set('parent_id', $this->parent->get('id'));
        }

        $this->set('level', $this->calculateLevel());
    }

    public function setParent(ManagedObject $object)
    {
        $this->parent = $object;
        return $this;
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
