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

    public function calculateLevel()
    {
        if ($this->parent === null) {
            return 0;
        } else {
            return $this->parent->calculateLevel() + 1;
        }
    }
}
