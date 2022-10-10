<?php

namespace Icinga\Module\Vspheredb\EventHistory;

use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class VmRecentMigrationHistory
{
    protected $vm;

    protected $db;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->db = $vm->getConnection()->getDbAdapter();
    }

    public function countWeeklyMigrationAttempts(): int
    {
        $query = $this->db->select()
            ->from('vm_event_history', 'COUNT(*)')
            ->where('vm_uuid = ?', DbUtil::quoteBinaryCompat($this->vm->get('uuid'), $this->db))
            ->where('ts_event_ms > ?', (time() - 86400 * 7) * 1000)
            ->where('event_type = ?', 'VmEmigratingEvent');

        return (int) $this->db->fetchOne($query);
    }
}
