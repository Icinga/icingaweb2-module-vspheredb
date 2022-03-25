<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db;

class VCenterServer extends BaseDbObject
{
    protected $table = 'vcenter_server';

    protected $autoincKeyName = 'id';

    protected $defaultProperties = [
        'id'              => null,
        'vcenter_id'      => null,
        'scheme'          => null,
        'host'            => null,
        'username'        => null,
        'password'        => null,
        'proxy_type'      => null,
        'proxy_address'   => null,
        'proxy_user'      => null,
        'proxy_pass'      => null,
        'ssl_verify_peer' => null,
        'ssl_verify_host' => null,
        'enabled'         => null,
    ];

    /**
     * @param Db $db
     * @return VCenterServer[]
     */
    public static function loadEnabledServers(Db $db)
    {
        return static::loadAll(
            $db,
            $db->getDbAdapter()->select()->from('vcenter_server')->where('enabled = ?', 'y'),
            'id'
        );
    }
}
