<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\Json\JsonString;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition;

class MonitoringRuleSet
{
    public const TABLE = 'monitoring_rule_set';
    public const NO_OBJECT = '';

    /** @var string */
    protected $binaryUuid;

    /** @var string */
    protected $objectFolder;

    /** @var ?bool */
    protected $enabled = null;

    /** @var MonitoringRuleSetDefinition */
    protected $definition;

    /** @var Settings */
    protected $settings;

    protected $fromDb = false;

    protected static $preloadCache = null;

    public function __construct(string $binaryUuid, string $objectFolder, ?Settings $settings = null)
    {
        $this->binaryUuid = $binaryUuid;
        if ($settings === null) {
            $this->settings = new Settings();
        } else {
            $this->settings = $settings;
        }
        $this->objectFolder = $objectFolder;
    }

    public static function loadOptionalForUuid(string $uuid, string $objectFolder, Db $connection): ?MonitoringRuleSet
    {
        if (self::$preloadCache !== null) {
            $key = self::makeKey($uuid, $objectFolder);
            return self::$preloadCache[$key] ?? null;
        }

        $db = $connection->getDbAdapter();
        $settings = $db->fetchOne(
            $db->select()
                ->from(MonitoringRuleSet::TABLE, 'settings')
                ->where('object_uuid = ?', $uuid)
                ->where('object_folder = ?', $objectFolder)
        );
        if ($settings) {
            $self = new static($uuid, $objectFolder, Settings::fromSerialization(JsonString::decode($settings)));
            $self->fromDb = true;
            return $self;
        }

        return null;
    }

    protected static function makeKey($objectUuid, $objectFolder): string
    {
        // correct would be using UUID, but bin2hex() is faster, and this is internal only
        return ($objectUuid === null ? 'null' : bin2hex($objectUuid))
            . '|'
            . json_encode($objectFolder);
    }

    public static function preloadAll(Db $connection)
    {
        $db = $connection->getDbAdapter();
        self::$preloadCache = [];
        foreach ($db->fetchAll($db->select()->from(MonitoringRuleSet::TABLE)) as $row) {
            $uuid = $row->object_uuid;
            $folder = $row->object_folder;
            self::$preloadCache[self::makeKey($uuid, $folder)]
                = new static($uuid, $folder, Settings::fromSerialization(JsonString::decode($row->settings)));
        }
    }

    public static function clearPreloadCache()
    {
        self::$preloadCache = null;
    }

    public function store(Db $connection): bool
    {
        $existing = self::loadOptionalForUuid($this->binaryUuid, $this->objectFolder, $connection);
        $db = $connection->getDbAdapter();
        if ($existing) {
            if ($this->settings->jsonSerialize() !== $existing->getSettings()->jsonSerialize()) {
                $rowCount = $db->update(MonitoringRuleSet::TABLE, [
                    'settings' => JsonString::encode($this->settings)
                ], $this->createWhere($connection));
                $this->fromDb = true;

                return $rowCount > 0;
            }

            return false;
        }

        $db->insert(MonitoringRuleSet::TABLE, [
            'object_uuid'   => $this->binaryUuid,
            'object_folder' => $this->objectFolder,
            'settings'      => JsonString::encode($this->settings),
        ]);
        $this->fromDb = true;

        return true;
    }

    public function delete(Db $connection): bool
    {
        $existing = self::loadOptionalForUuid($this->binaryUuid, $this->objectFolder, $connection);
        $db = $connection->getDbAdapter();
        if ($existing) {
            $rowCount = $db->delete(
                MonitoringRuleSet::TABLE,
                $this->createWhere($connection)
            );
            $this->fromDb = false;

            return $rowCount > 0;
        }

        return false;
    }

    protected function createWhere(Db $connection): string
    {
        $db = $connection->getDbAdapter();
        return $db->quoteInto('object_uuid = ?', $connection->quoteBinary($this->binaryUuid))
        . $db->quoteInto(' AND object_folder = ?', $this->objectFolder);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function hasBeenLoadedFromDb(): bool
    {
        return $this->fromDb;
    }

    /**
     * @return MonitoringRuleSetDefinition
     */
    public function getDefinition(): MonitoringRuleSetDefinition
    {
        return $this->definition;
    }

    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     * @param Settings $settings
     * @return MonitoringRuleSet
     */
    public function setSettings(Settings $settings): MonitoringRuleSet
    {
        $this->settings = $settings;
        return $this;
    }
}
