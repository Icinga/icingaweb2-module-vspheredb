<?php

namespace Icinga\Module\Vspheredb\Db;

use DirectoryIterator;
use Exception;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Data\Db\DbConnection;
use RuntimeException;

class Migrations
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var DbConnection */
    protected $connection;

    /** @var string */
    protected $migrationsDir;

    /** @var string */
    protected $tablePrefix;

    public function __construct(DbConnection $connection, $tablePrefix = null)
    {
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();
        if ($tablePrefix === null) {
            $this->tablePrefix = $this->getModuleName();
        } else {
            $this->tablePrefix = $tablePrefix;
        }
    }

    public function getLastMigrationNumber()
    {
        try {
            $query = $this->db->select()->from(
                ['m' => $this->getTableName()],
                ['schema_version' => 'MAX(schema_version)']
            );

            return (int) $this->db->fetchOne($query);
        } catch (Exception $e) {
            return 0;
        }
    }

    protected function getTableName()
    {
        return $this->getModuleName() . '_schema_migration';
    }

    /**
     * @return bool
     */
    public function hasModuleRelatedTable()
    {
        return in_array('virtual_machine', $this->db->listTables());
    }

    /**
     * @return bool
     */
    public function hasAnyTable()
    {
        return count($this->db->listTables()) > 0;
    }

    public function hasSchema()
    {
        return $this->listPendingMigrations() !== [0];
    }

    public function hasPendingMigrations()
    {
        return $this->countPendingMigrations() > 0;
    }

    public function countPendingMigrations()
    {
        return count($this->listPendingMigrations());
    }

    /**
     * @return Migration[]
     */
    public function getPendingMigrations()
    {
        $migrations = array();
        foreach ($this->listPendingMigrations() as $version) {
            $migrations[] = new Migration(
                $version,
                $this->loadMigrationFile($version)
            );
        }

        return $migrations;
    }

    /**
     * @return $this
     */
    public function applyPendingMigrations()
    {
        foreach ($this->getPendingMigrations() as $migration) {
            $migration->apply($this->connection);
        }

        return $this;
    }

    public function listPendingMigrations()
    {
        $lastMigration = $this->getLastMigrationNumber();
        if ($lastMigration === 0) {
            return [0];
        }

        return $this->listMigrationsAfter($this->getLastMigrationNumber());
    }

    public function listAllMigrations()
    {
        $dir = $this->getMigrationsDir();
        if (! is_readable($dir)) {
            return [];
        }

        $versions = [];

        foreach (new DirectoryIterator($this->getMigrationsDir()) as $file) {
            if ($file->isDot()) {
                continue;
            }

            $filename = $file->getFilename();
            if (preg_match('/^upgrade_(\d+)\.sql$/', $filename, $match)) {
                $versions[] = $match[1];
            }
        }

        sort($versions);

        return $versions;
    }

    public function loadMigrationFile($version)
    {
        if ($version === 0) {
            $filename = $this->getFullSchemaFile();
        } else {
            $filename = sprintf(
                '%s/upgrade_%d.sql',
                $this->getMigrationsDir(),
                $version
            );
        }

        return file_get_contents($filename);
    }

    protected function listMigrationsAfter($version)
    {
        $filtered = [];
        foreach ($this->listAllMigrations() as $available) {
            if ($available > $version) {
                $filtered[] = $available;
            }
        }

        return $filtered;
    }

    protected function getMigrationsDir()
    {
        if ($this->migrationsDir === null) {
            $this->migrationsDir = $this->getSchemaDir(
                $this->connection->getDbType() . '-migrations'
            );
        }

        return $this->migrationsDir;
    }

    protected function getFullSchemaFile()
    {
        return $this->getSchemaDir(
            $this->connection->getDbType() . '.sql'
        );
    }

    protected function getSchemaDir($sub = null)
    {
        try {
            $dir = $this->getModuleDir('/schema');
        } catch (ProgrammingError $e) {
            throw new RuntimeException(
                'Unable to detect the schema directory for this module',
                0,
                $e
            );
        }
        if ($sub === null) {
            return $dir;
        } else {
            return $dir . '/' . ltrim($sub, '/');
        }
    }

    /**
     * @param string $sub
     * @return string
     * @throws ProgrammingError
     */
    protected function getModuleDir($sub = '')
    {
        return Icinga::app()->getModuleManager()->getModuleDir(
            $this->getModuleName(),
            $sub
        );
    }

    protected function getModuleName()
    {
        return $this->getModuleNameForObject($this);
    }

    protected function getModuleNameForObject($object)
    {
        $class = get_class($object);

        // Hint: Icinga\Module\ -> 14 chars
        return lcfirst(substr($class, 14, strpos($class, '\\', 15) - 14));
    }
}
