<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Application\Config;
use Icinga\Module\Vspheredb\Db\DbConnection;

class Db extends DbConnection
{
    public static function newConfiguredInstance()
    {
        return static::fromResourceName(
            Config::module('vspheredb')->get('db', 'resource')
        );
    }
}
