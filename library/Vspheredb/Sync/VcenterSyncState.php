<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Util;

class VcenterSyncState
{
    /** @var VCenter */
    protected $vCenter;

    /** @var \stdClass */
    protected $info;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    public function isAlive()
    {
        $info = $this->getInfo();
        if ($info === null) {
            return false;
        }

        return abs(Util::currentTimestamp() - $info->ts_last_refresh) < 5000;
    }

    public function getInfo()
    {
        if ($this->info === null) {
            $this->refreshInfo();
        }

        return $this->info;
    }

    public function refreshInfo()
    {
        $db = $this->vCenter->getDb();
        $result = $db->fetchRow(
            $db->select()
                ->from('vcenter_sync')
                ->where('vcenter_uuid = ?', $this->vCenter->getUuid())
        );

        if ($result) {
            $this->info = $result;
        }

        return $this;
    }
}
