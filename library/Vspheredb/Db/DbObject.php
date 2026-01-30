<?php

namespace Icinga\Module\Vspheredb\Db;

use gipfl\Json\JsonString;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Exception\DuplicateKeyException;
use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Exception;
use Zend_Db_Expr;
use Zend_Db_Select;

/**
 * Cloned from Director. Should sooner or later be replaced by something modern
 */
abstract class DbObject
{
    /** @var ?DbConnection $connection */
    protected ?DbConnection $connection = null;

    /** @var ?string Table name. MUST be set when extending this class */
    protected ?string $table = null;

    /** @var ?Zend_Db_Adapter_Abstract */
    protected ?Zend_Db_Adapter_Abstract $db = null;

    /**
     * Default columns. MUST be set when extending this class. Each table
     * column MUST be defined with a default value. Default value may be null.
     *
     * @var ?array
     */
    protected ?array $defaultProperties = null;

    /** @var ?array Properties as loaded from db */
    protected ?array $loadedProperties = null;

    /** @var bool Whether at least one property has been modified */
    protected bool $hasBeenModified = false;

    /** @var bool Whether this object has been loaded from db */
    protected bool $loadedFromDb = false;

    /** @var array Object properties */
    protected array $properties = [];

    /** @var array Property names that have been modified since object creation */
    protected array $modifiedProperties = [];

    /** @var string|string[]|null Unique key name, could be primary */
    protected string|array|null $keyName = null;

    /** @var ?string Set this to an eventual autoincrementing column. May equal $keyName */
    protected ?string $autoincKeyName = null;

    /** @var bool forbid updates to autoinc values */
    protected bool $protectAutoinc = true;

    /** @var array */
    protected array $binaryProperties = [];

    /**
     * Constructor is not accessible and should not be overridden
     */
    protected function __construct()
    {
        if (
            $this->table === null
            || $this->keyName === null
            || $this->defaultProperties === null
        ) {
            throw new LogicException("Someone extending this class didn't RTFM");
        }

        $this->properties = $this->defaultProperties;
        $this->beforeInit();
    }

    /**
     * @return string|null
     */
    public function getTableName(): ?string
    {
        return $this->table;
    }

    /************************************************************************\
     * When extending this class one might want to override any of the      *
     * following hooks. Try to use them whenever possible, especially       *
     * instead of overriding other essential methods like store().          *
    \************************************************************************/

    /**
     * One can override this to allow for cross checks and more before storing
     * the object. Please note that the method is public and allows to check
     * object consistence at any time.
     *
     * @return boolean  Whether this object is valid
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * This is going to be executed before any initialization method takes *
     * (load from DB, populate from Array...) takes place
     *
     * @return void
     */
    protected function beforeInit(): void
    {
    }

    /**
     * Will be executed every time an object has successfully been loaded from
     * Database
     *
     * @return void
     */
    protected function onLoadFromDb(): void
    {
    }

    /**
     * Will be executed before an Object is going to be stored. In case you
     * want to prevent the store() operation from taking place, please throw
     * an Exception.
     *
     * @return void
     */
    protected function beforeStore(): void
    {
    }

    /**
     * Wird ausgeführt, nachdem ein Objekt erfolgreich gespeichert worden ist
     *
     * @return void
     */
    protected function onStore(): void
    {
    }

    /**
     * Wird ausgeführt, nachdem ein Objekt erfolgreich der Datenbank hinzu-
     * gefügt worden ist
     *
     * @return void
     */
    protected function onInsert(): void
    {
    }

    /**
     * Wird ausgeführt, nachdem bestehendes Objekt erfolgreich der Datenbank
     * geändert worden ist
     *
     * @return void
     */
    protected function onUpdate(): void
    {
    }

    /**
     * Wird ausgeführt, bevor ein Objekt gelöscht wird. Die Operation wird
     * aber auf jeden Fall durchgeführt, außer man wirft eine Exception
     *
     * @return void
     */
    protected function beforeDelete(): void
    {
    }

    /**
     * Wird ausgeführt, nachdem bestehendes Objekt erfolgreich aud der
     * Datenbank gelöscht worden ist
     *
     * @return void
     */
    protected function onDelete(): void
    {
    }

    /**
     * Set database connection
     *
     * @param DbConnection|null $connection Database connection
     *
     * @return $this
     */
    public function setConnection(?DbConnection $connection): static
    {
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();

        return $this;
    }

    /**
     * Getter
     *
     * @param string $property Property
     *
     * @return mixed
     */
    public function get(string $property): mixed
    {
        $func = 'get' . ucfirst($property);
        if (substr($func, -2) === '[]') {
            $func = substr($func, 0, -2);
        }
        // TODO: id check avoids collision with getId. Rethink this.
        if ($property !== 'id' && method_exists($this, $func)) {
            return $this->$func();
        }

        $this->assertPropertyExists($property);
        return $this->properties[$property];
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getProperty(string $key): mixed
    {
        $this->assertPropertyExists($key);

        return $this->properties[$key];
    }

    /**
     * @param string $key
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function assertPropertyExists(string $key): static
    {
        if (! array_key_exists($key, $this->properties)) {
            throw new InvalidArgumentException(sprintf(
                'Trying to get invalid property "%s"',
                $key
            ));
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasProperty(string $key): bool
    {
        if (array_key_exists($key, $this->properties)) {
            return true;
        } elseif ($key === 'id') {
            // There is getId, would give false positive
            return false;
        }
        $func = 'get' . ucfirst($key);
        if (substr($func, -2) === '[]') {
            $func = substr($func, 0, -2);
        }
        if (method_exists($this, $func)) {
            return true;
        }
        return false;
    }

    /**
     * Generic setter
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this|null
     */
    public function set(string $key, mixed $value): ?static
    {
        if ($value === '') {
            $value = null;
        }

        $func = 'validate' . ucfirst($key);
        if (method_exists($this, $func) && $this->$func($value) !== true) {
            throw new InvalidArgumentException(sprintf(
                'Got invalid value "%s" for "%s"',
                $value,
                $key
            ));
        }
        $func = 'munge' . ucfirst($key);
        if (method_exists($this, $func)) {
            $value = $this->$func($value);
        }

        $func = 'set' . ucfirst($key);
        if (substr($func, -2) === '[]') {
            $func = substr($func, 0, -2);
        }

        if (method_exists($this, $func)) {
            return $this->$func($value);
        }

        if (! $this->hasProperty($key)) {
            throw new InvalidArgumentException(sprintf(
                'Trying to set invalid key %s',
                $key
            ));
        }

        if (
            (is_numeric($value) || is_string($value))
            && (string) $value === (string) $this->get($key)
        ) {
            return $this;
        }

        if ($key === $this->getAutoincKeyName()  && $this->hasBeenLoadedFromDb()) {
            throw new InvalidArgumentException('Changing autoincremental key is not allowed');
        }

        return $this->reallySet($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    protected function reallySet(string $key, mixed $value): static
    {
        if ($value === $this->properties[$key]) {
            return $this;
        }

        $this->hasBeenModified = true;
        $this->modifiedProperties[$key] = true;
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Magic getter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Magic setter
     *
     * @param string $key Key
     * @param  mixed $val Value
     *
     * @return void
     */
    public function __set(string $key, mixed $val): void
    {
        $this->set($key, $val);
    }

    /**
     * Magic isset check
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * Magic unsetter
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset(string $key): void
    {
        if (! array_key_exists($key, $this->properties)) {
            throw new InvalidArgumentException('Trying to unset invalid key');
        }
        $this->properties[$key] = $this->defaultProperties[$key];
    }

    /**
     * Runs set() for every key/value pair of the given Array
     *
     * @param array $props Array of properties
     *
     * @return $this
     */
    public function setProperties(array $props): static
    {
        foreach ($props as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * Return an array with all object properties
     *
     * @return array
     */
    public function getProperties(): array
    {
        //return $this->properties;
        $res = [];
        foreach ($this->listProperties() as $key) {
            $res[$key] = $this->get($key);
        }

        return $res;
    }

    /**
     * @return array|null
     */
    protected function getPropertiesForDb(): ?array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function listProperties(): array
    {
        return array_keys($this->properties);
    }

    /**
     * Return all properties that changed since object creation
     *
     * @return array
     */
    public function getModifiedProperties(): array
    {
        $props = [];
        foreach (array_keys($this->modifiedProperties) as $key) {
            if ($key === $this->autoincKeyName) {
                if ($this->protectAutoinc) {
                    continue;
                } elseif ($this->properties[$key] === null) {
                    continue;
                }
            }

            $props[$key] = $this->properties[$key];
        }
        return $props;
    }

    /**
     * List all properties that changed since object creation
     *
     * @return array
     */
    public function listModifiedProperties(): array
    {
        return array_keys($this->modifiedProperties);
    }

    /**
     * Whether this object has been modified
     *
     * @return bool
     */
    public function hasBeenModified(): bool
    {
        return $this->hasBeenModified;
    }

    /**
     * Whether the given property has been modified
     *
     * @param string $key Property name
     *
     * @return bool
     */
    protected function hasModifiedProperty(string $key): bool
    {
        return array_key_exists($key, $this->modifiedProperties);
    }

    /**
     * Unique key name
     *
     * @return string[]|string
     */
    public function getKeyName(): array|string
    {
        return $this->keyName;
    }

    /**
     * Autoinc key name
     *
     * @return ?string
     */
    public function getAutoincKeyName(): ?string
    {
        return $this->autoincKeyName;
    }

    /**
     * @return array
     */
    public function getKeyParams(): array
    {
        $params = [];
        $key = $this->getKeyName();
        if (is_array($key)) {
            /** @var string $k */
            foreach ($key as $k) {
                $params[$k] = $this->get($k);
            }
        } else {
            $params[$key] = $this->get($this->keyName);
        }

        return $params;
    }

    /**
     * Return the unique identifier
     *
     * // TODO: may conflict with ->id
     *
     * @return string|array|null
     *
     * @throws InvalidArgumentException When key can not be calculated
     */
    public function getId(): array|string|null
    {
        $keyName = $this->getKeyName();
        if (is_array($keyName)) {
            $id = [];
            /** @var string $key */
            foreach ($keyName as $key) {
                if (isset($this->properties[$key])) {
                    $id[$key] = $this->properties[$key];
                }
            }

            if (empty($id)) {
                throw new InvalidArgumentException('Could not evaluate id for multi-column object!');
            }

            return $id;
        } else {
            if (isset($this->properties[$keyName])) {
                return $this->properties[$keyName];
            }
        }
        return null;
    }

    /**
     * Get the autoinc value if set
     *
     * @return int|null
     */
    public function getAutoincId(): ?int
    {
        $autoincKeyName = $this->getAutoincKeyName();
        if ($autoincKeyName !== null && isset($this->properties[$autoincKeyName])) {
            return (int) $this->properties[$autoincKeyName];
        }
        return null;
    }

    /**
     * @return $this
     */
    protected function forgetAutoincId(): static
    {
        $autoincKeyName = $this->getAutoincKeyName();
        if ($autoincKeyName !== null && isset($this->properties[$autoincKeyName])) {
            $this->properties[$autoincKeyName] = null;
        }

        return $this;
    }

    /**
     * Liefert das benutzte Datenbank-Handle
     *
     * @return Zend_Db_Adapter_Abstract|null
     */
    public function getDb(): ?Zend_Db_Adapter_Abstract
    {
        return $this->db;
    }

    public function hasConnection(): bool
    {
        return $this->connection !== null;
    }

    /**
     * @return DbConnection|null
     */
    public function getConnection(): ?DbConnection
    {
        return $this->connection;
    }

    /**
     * Lädt einen Datensatz aus der Datenbank und setzt die entsprechenden
     * Eigenschaften dieses Objekts
     *
     * @return $this
     *
     * @throws NotFoundError
     */
    protected function loadFromDb(): static
    {
        $select = $this->db->select()->from($this->table)->where($this->createWhere());
        $properties = $this->db->fetchRow($select);

        if (empty($properties)) {
            if (is_array($this->getKeyName())) {
                throw new NotFoundError(
                    'Failed to load %s for %s',
                    $this->table,
                    $this->createWhere()
                );
            } else {
                throw new NotFoundError(
                    'Failed to load %s "%s"',
                    $this->table,
                    $this->getLogId()
                );
            }
        }

        return $this->setDbProperties($properties);
    }

    /**
     * @param object $row
     * @param Db     $db
     *
     * @return static
     */
    public static function fromDbRow(object $row, Db $db): static
    {
        return (new static())
            ->setConnection($db)
            ->setDbProperties($row);
    }

    /**
     * @param array|stdClass $properties
     *
     * @return $this
     */
    public function setDbProperties(array|stdClass $properties): static
    {
        foreach ($properties as $key => $val) {
            if (! array_key_exists($key, $this->properties)) {
                throw new LogicException(sprintf(
                    'Trying to set invalid %s key "%s". DB schema change?',
                    $this->table,
                    $key
                ));
            }
            if ($val === null) {
                $this->properties[$key] = null;
            } elseif (is_resource($val)) {
                $this->properties[$key] = stream_get_contents($val);
            } else {
                $this->properties[$key] = (string) $val;
            }
        }

        $this->loadedFromDb = true;
        $this->loadedProperties = $this->properties;
        $this->hasBeenModified = false;
        $this->modifiedProperties = [];
        $this->onLoadFromDb();
        return $this;
    }

    /**
     * @return array|null
     */
    public function getOriginalProperties(): ?array
    {
        return $this->loadedProperties;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOriginalProperty(string $key): mixed
    {
        $this->assertPropertyExists($key);
        if ($this->hasBeenLoadedFromDb()) {
            return $this->loadedProperties[$key];
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function resetProperty(string $key): static
    {
        $this->set($key, $this->getOriginalProperty($key));
        if ($this->listModifiedProperties() === [$key]) {
            $this->hasBeenModified = false;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBeenLoadedFromDb(): bool
    {
        return $this->loadedFromDb;
    }

    /**
     * Ändert den entsprechenden Datensatz in der Datenbank
     *
     * @return int|true Anzahl der geänderten Zeilen
     *
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function updateDb(): int|true
    {
        $properties = $this->getModifiedProperties();
        if (empty($properties)) {
            // Fake true, we might have manually set this to "modified"
            return true;
        }

        // TODO: Remember changed data for audit and log
        return $this->db->update(
            $this->table,
            $properties,
            $this->createWhere()
        );
    }

    /**
     * Fügt der Datenbank-Tabelle einen entsprechenden Datensatz hinzu
     *
     * @return int Anzahl der betroffenen Zeilen
     *
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function insertIntoDb(): int
    {
        $properties = $this->getPropertiesForDb();
        $autoincKeyName = $this->getAutoincKeyName();
        if ($autoincKeyName !== null) {
            if ($this->protectAutoinc || $properties[$autoincKeyName] === null) {
                unset($properties[$autoincKeyName]);
            }
        }
        // TODO: Remove this!
        if ($this->connection->isPgsql()) {
            foreach ($properties as $key => $value) {
                if ($this->isBinaryColumn($key)) {
                    $properties[$key] = DbConnection::pgBinEscape($value);
                }
            }
        }

        return $this->db->insert($this->table, $properties);
    }

    /**
     * @param string $column
     *
     * @return bool
     */
    protected function isBinaryColumn(string $column): bool
    {
        return in_array($column, $this->binaryProperties);
    }

    /**
     * Store object to database
     *
     * @param DbConnection|null $db
     *
     * @return true Whether storing succeeded
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws DuplicateKeyException
     */
    public function store(?DbConnection $db = null): true
    {
        if ($db !== null) {
            $this->setConnection($db);
        }

        if ($this->validate() !== true) {
            throw new InvalidArgumentException(sprintf(
                '%s[%s] validation failed',
                $this->table,
                $this->getLogId()
            ));
        }

        if ($this->hasBeenLoadedFromDb() && ! $this->hasBeenModified()) {
            return true;
        }

        $this->beforeStore();
        $table = $this->table;
        $id = $this->getId();

        try {
            if ($this->hasBeenLoadedFromDb()) {
                if ($this->updateDb() !== false) {
                    $result = true;
                    $this->onUpdate();
                } else {
                    throw new RuntimeException(sprintf(
                        'FAILED storing %s "%s"',
                        $table,
                        $this->getLogId()
                    ));
                }
            } else {
                $autoincKeyName = $this->getAutoincKeyName();
                if ($id && $this->existsInDb()) {
                    $logId = '"' . $this->getLogId() . '"';

                    if ($autoId = $this->getAutoincId()) {
                        $logId .= sprintf(', %s=%s', $autoincKeyName, $autoId);
                    }
                    throw new DuplicateKeyException(
                        'Trying to recreate %s (%s)',
                        $table,
                        $logId
                    );
                }

                if ($this->insertIntoDb()) {
                    if ($autoincKeyName && $this->getProperty($autoincKeyName) === null) {
                        if ($this->connection->isPgsql()) {
                            $this->properties[$autoincKeyName] = $this->db->lastInsertId($table, $autoincKeyName);
                        } else {
                            $this->properties[$autoincKeyName] = $this->db->lastInsertId();
                        }
                    }
                    // $this->log(sprintf('New %s "%s" has been stored', $table, $id));
                    $this->onInsert();
                    $result = true;
                } else {
                    throw new RuntimeException(sprintf(
                        'FAILED to store new %s "%s"',
                        $table,
                        $this->getLogId()
                    ));
                }
            }
        } catch (Zend_Db_Exception $e) {
            throw new RuntimeException(sprintf(
                'Storing %s[%s] failed: %s {%s}',
                $this->table,
                $this->getLogId(),
                $e->getMessage(),
                var_export($this->getProperties(), 1) // TODO: Remove properties
            ));
        }

        $this->modifiedProperties = [];
        $this->hasBeenModified = false;
        $this->loadedProperties = $this->properties;
        $this->onStore();
        $this->loadedFromDb = true;

        return $result;
    }

    /**
     * Delete item from DB
     *
     * @return int  Affected rows
     */
    protected function deleteFromDb(): int
    {
        return $this->db->delete(
            $this->table,
            $this->createWhere()
        );
    }

    /**
     * @param string[]|string $key
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    protected function setKey(array|string $key): static
    {
        $keyname = $this->getKeyName();
        if (is_array($keyname)) {
            if (! is_array($key)) {
                throw new InvalidArgumentException(sprintf(
                    '%s has a multicolumn key, array required',
                    $this->table
                ));
            }
            /** @var string $k */
            foreach ($keyname as $k) {
                if (! array_key_exists($k, $key)) {
                    // We allow for null in multicolumn keys:
                    $key[$k] = null;
                }
                $this->set($k, $key[$k]);
            }
        } else {
            $this->set($keyname, $key);
        }
        return $this;
    }

    /**
     * @return bool
     */
    protected function existsInDb(): bool
    {
        $result = $this->db->fetchRow(
            $this->db->select()->from($this->table)->where($this->createWhere())
        );
        return $result !== false;
    }

    /**
     * @return string
     */
    public function createWhere(): string
    {
        if ($id = $this->getAutoincId()) {
            if ($originalId = $this->getOriginalProperty($this->autoincKeyName)) {
                return $this->db->quoteInto(
                    sprintf('%s = ?', $this->autoincKeyName),
                    $originalId
                );
            }
            return $this->db->quoteInto(
                sprintf('%s = ?', $this->autoincKeyName),
                $id
            );
        }

        $key = $this->getKeyName();

        if (is_array($key) && ! empty($key)) {
            $where = [];
            /** @var string $k */
            foreach ($key as $k) {
                if ($this->hasBeenLoadedFromDb()) {
                    if ($this->loadedProperties[$k] === null) {
                        $where[] = sprintf('%s IS NULL', $k);
                    } else {
                        $where[] = $this->createQuotedWhere($k, $this->loadedProperties[$k]);
                    }
                } else {
                    if ($this->properties[$k] === null) {
                        $where[] = sprintf('%s IS NULL', $k);
                    } else {
                        $where[] = $this->createQuotedWhere($k, $this->properties[$k]);
                    }
                }
            }

            return implode(' AND ', $where);
        } else {
            if ($this->hasBeenLoadedFromDb()) {
                return $this->createQuotedWhere($key, $this->loadedProperties[$key]);
            } else {
                return $this->createQuotedWhere($key, $this->properties[$key]);
            }
        }
    }

    /**
     * @param string $column
     * @param mixed  $value
     *
     * @return string
     */
    protected function createQuotedWhere(string $column, mixed $value): string
    {
        return $this->db->quoteInto(
            sprintf('%s = ?', $column),
            $this->eventuallyQuoteBinary($value, $column)
        );
    }

    /**
     * @param mixed  $value
     * @param string $column
     *
     * @return array|mixed|string|Zend_Db_Expr
     */
    protected function eventuallyQuoteBinary(mixed $value, string $column): mixed
    {
        if ($this->isBinaryColumn($column)) {
            return $this->connection->quoteBinary($value);
        } else {
            return $value;
        }
    }

    /**
     * @return mixed|string
     */
    protected function getLogId(): mixed
    {
        if (is_array($this->keyName)) {
            $id = [];
            /** @var string $key */
            foreach ($this->keyName as $key) {
                if (isset($this->properties[$key])) {
                    $id[$key] = $this->getReadableProperty($key);
                }
            }
            try {
                return JsonString::encode($id);
            } catch (\Exception $e) {
                return 'Key encoding failed: ' . $e->getMessage();
            }
        }

        return $this->getReadableProperty($this->keyName);
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    protected function getReadableProperty(string $name): mixed
    {
        if (isset($this->properties[$name])) {
            $value = $this->properties[$name];
            if (preg_match('/uuid$/', $name) && strlen($value) === 16) {
                return Uuid::fromBytes($value)->toString();
            }

            return $value;
        }

        return $this->defaultProperties[$name];
    }

    /**
     * @return true
     */
    public function delete(): true
    {
        $table = $this->table;

        if (! $this->hasBeenLoadedFromDb()) {
            throw new LogicException(sprintf(
                'Cannot delete %s "%s", it has not been loaded from Db',
                $table,
                $this->getLogId()
            ));
        }

        if (! $this->existsInDb()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot delete %s "%s", it does not exist',
                $table,
                $this->getLogId()
            ));
        }
        $this->beforeDelete();
        if (! $this->deleteFromDb()) {
            throw new RuntimeException(sprintf(
                'Deleting %s (%s) FAILED',
                $table,
                $this->getLogId()
            ));
        }
        // $this->log(sprintf('%s "%s" has been DELETED', $table, this->getLogId()));
        $this->onDelete();
        $this->loadedFromDb = false;
        return true;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->onClone();
        $this->forgetAutoincId();
        $this->loadedFromDb    = false;
        $this->hasBeenModified = true;
    }

    /**
     * @return void
     */
    protected function onClone(): void
    {
    }

    /**
     * @param array             $properties
     * @param DbConnection|null $connection
     *
     * @return static
     */
    public static function create(array $properties = [], ?DbConnection $connection = null): static
    {
        $obj = new static();
        if ($connection !== null) {
            $obj->setConnection($connection);
        }
        $obj->setProperties($properties);
        return $obj;
    }

    /**
     * @param int|string $id
     * @param DbConnection|null $connection
     *
     * @return static
     *
     * @throws NotFoundError
     */
    public static function loadWithAutoIncId(int|string $id, ?DbConnection $connection): static
    {
        /* Need to cast to int, otherwise the id will be matched against
         * object_name, which may wreak havoc if an object has a
         * object_name matching some id. Note that DbObject::set() and
         * DbObject::setDbProperties() will convert any property to
         * string, including ids.
         */
        $id = (int) $id;

        $obj = new static();
        $obj->setConnection($connection)
            ->set($obj->autoincKeyName, $id)
            ->loadFromDb();

        return $obj;
    }

    /**
     * @param string       $id
     * @param DbConnection $connection
     *
     * @return static
     *
     * @throws NotFoundError
     */
    public static function load(string $id, DbConnection $connection): static
    {
        $obj = new static();
        $obj->setConnection($connection)->setKey($id)->loadFromDb();

        return $obj;
    }

    /**
     * @param DbConnection        $connection
     * @param Zend_Db_Select|null $query
     * @param string|null         $keyColumn
     *
     * @return static[]
     */
    public static function loadAll(
        DbConnection $connection,
        ?Zend_Db_Select $query = null,
        ?string $keyColumn = null
    ): array {
        $objects = [];
        $db = $connection->getDbAdapter();

        if ($query === null) {
            $dummy = new static();
            $select = $db->select()->from($dummy->table);
        } else {
            $select = $query;
        }
        $rows = $db->fetchAll($select);

        foreach ($rows as $row) {
            /** @var DbObject $obj */
            $obj = new static();
            $obj->setConnection($connection)->setDbProperties($row);
            if ($keyColumn === null) {
                $objects[] = $obj;
            } else {
                if (is_array($keyColumn)) {
                    $key = '';
                    foreach ($keyColumn as $part) {
                        $key .= $obj->get($part);
                    }
                } else {
                    $key = $row->$keyColumn;
                }
                $objects[$key] = $obj;
            }
        }

        return $objects;
    }

    /**
     * @param $id
     * @param DbConnection $connection
     *
     * @return bool
     */
    public static function exists($id, DbConnection $connection): bool
    {
        $obj = new static();
        $obj->setConnection($connection)->setKey($id);
        return $obj->existsInDb();
    }

    public function __destruct()
    {
        unset($this->db);
        unset($this->connection);
    }
}
