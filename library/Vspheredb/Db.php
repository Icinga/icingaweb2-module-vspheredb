<?php

namespace Icinga\Module\Vspheredb;

use gipfl\DbMigration\Migrations;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Module\Vspheredb\Db\DbConnection;
use Zend_Db_Adapter_Pdo_Abstract;

class Db extends DbConnection
{
    public static function newConfiguredInstance()
    {
        return static::fromResourceName(
            Config::module('vspheredb')->get('db', 'resource')
        );
    }

    public static function migrationsForDb(Db $connection)
    {
        $db = $connection->getDbAdapter();
        assert($db instanceof Zend_Db_Adapter_Pdo_Abstract);

        return new Migrations(
            $db,
            Icinga::app()->getModuleManager()->getModuleDir('vspheredb', '/schema'),
            'vspheredb_schema_migration'
        );
    }
}
