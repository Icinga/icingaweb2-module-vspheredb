<?php

namespace Icinga\Module\Vspheredb\Rpc;

use Exception;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Util;

class LogProxy
{
    protected $connection;

    protected $db;

    protected $vCenterUuid;

    protected $server;

    protected $instanceUuid;

    public function __construct(Db $connection, $instanceUuid)
    {
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();
        $this->instanceUuid = $instanceUuid;
    }

    public function setServer(VCenterServer $server)
    {
        $this->server = $server;
    }

    public function setVCenter(VCenter $vCenter)
    {
        $this->vCenterUuid = $vCenter->get('instance_uuid');

        return $this;
    }

    public function log($severity, $message)
    {
        Logger::$severity($message);
        try {
            $this->db->insert('vspheredb_daemonlog', [
                'vcenter_uuid'  => $this->vCenterUuid ?: str_repeat('0', 16),
                'instance_uuid' => $this->instanceUuid,
                'ts_create'     => Util::currentTimestamp(),
                'level'         => $severity,
                'message'       => $message,
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }
    }
}
