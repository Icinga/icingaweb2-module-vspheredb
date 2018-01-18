<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\SelectSet\FullSelectSet;

class IdLookup
{
    /** @var Api */
    private $api;

    /** @var array */
    private $idToName = array();

    /** @var array */
    private $idToType = array();

    /** @var array */
    private $idToParent = array();

    private $lastLookup;

    private $objects = array();

    /** @var int */
    private $cacheTimeout = 120;

    /** @var Db */
    private $db;

    /**
     * IdLookup constructor.
     * @param Api $api
     * @param Db $db
     */
    public function __construct(Api $api, Db $db)
    {
        $this->api = $api;
        $this->db = $db;
    }

    /**
     * @param string$id
     * @return string|null
     */
    public function getNameForId($id)
    {
        $this->requireFreshMaps();
        if (array_key_exists($id, $this->idToName)) {
            return $this->idToName[$id];
        }

        return null;
    }

    /**
     * @param string$id
     * @return string|null
     */
    public function getTypeForId($id)
    {
        $this->requireFreshMaps();
        if (array_key_exists($id, $this->idToType)) {
            return $this->idToType[$id];
        }

        return null;
    }

    /**
     * @param string$id
     * @return string|null
     */
    public function getParentForId($id)
    {
        $this->requireFreshMaps();
        if (array_key_exists($id, $this->idToParent)) {
            return $this->idToParent[$id];
        }

        return null;
    }

    /**
     * Returns a string representing all parents of the given id
     *
     * @param $id
     * @param string $separator
     * @return string
     */
    public function getInheritanceNamePathToId($id, $separator = ' -> ')
    {
        $path = $this->getPathToId($id);
        $names = array();
        foreach ($path as $id) {
            $names[] = $this->getNameForId($id);
        }

        return implode($separator, $names);
    }

    /**
     * Returns an array with all ids from root to the given id
     *
     * @param $id
     * @return array
     */
    public function getPathToId($id)
    {
        $path = array();
        $current = $id;
        while (null !== $current = $this->getParentForId($current)) {
            array_unshift($path, $current);
        }

        return $path;
    }

    /**
     * Lookup all known id-based references for the given objects
     *
     * @param $objects
     */
    public function enrichObjects($objects)
    {
        foreach ($objects as $object) {
            $object->folder = $this->getInheritanceNamePathToId($object->id);
            $object->parent = $this->getNameForId($object->parent);

            if (property_exists($object, 'runtime.host')) {
                $object->{'runtime.host'} = $this->getNameForId($object->{'runtime.host'});
            }

            if (property_exists($object, 'vm')) {
                foreach ($object->vm as $k => $id) {
                    $object->vm[$k] = $this->getNameForId($id);
                }
            }
        }
    }

    /**
     * Refresh our internal ID cache
     * @return $this
     * @throws ProgrammingError
     */
    public function refresh()
    {
        Benchmark::measure('Ready to fetch id/name/parent list');
        $all = $this->fetchNames();
        Benchmark::measure(sprintf("Got id/name/parent for %d objects", count($all)));
        $this->objects = ManagedObject::loadAll($this->db, null, 'id');
        $db = $this->db;
        $fetched = [];
        foreach ($all as $obj) {
            $id = Util::extractNumericId($obj->id);
            $fetched[$id] = $obj->name;
            if (array_key_exists($id, $this->objects)) {
                $object = $this->objects[$id];
                if ($object->get('moref') !== $obj->id) {
                    throw new ProgrammingError(
                        'MoId sync failed, %s is %s and %s',
                        $id,
                        $obj->id,
                        $object->get('moref')
                    );
                }
                $object->set('moref', $obj->id);
                $object->set('object_name', $obj->name);
                $object->set('object_type', $obj->type);
                $object->set('overall_status', $obj->overallStatus);
            } else {
                $this->objects[$id] = ManagedObject::create([
                    'id'          => $id,
                    'moref'       => $obj->id,
                    'object_name' => $obj->name,
                    'object_type' => $obj->type,
                    'overall_status' => $obj->overallStatus,
                ], $db);
            }
            $this->idToName[$obj->id] = $obj->name;
            $this->idToType[$obj->id] = $obj->type;
            if (property_exists($obj, 'parent')) {
                $this->idToParent[$obj->id] = $obj->parent;
            }
        }

        foreach ($this->idToParent as $id => $parentId) {
            $this->objects[Util::extractNumericId($id)]->setParent(
                $this->objects[Util::extractNumericId($parentId)]
            );
        }

        Benchmark::measure('Storing object tree to DB');
        $dba = $db->getDbAdapter();
        $dba->beginTransaction();
        $new = [];
        $same = [];
        $del = [];
        $mod = [];
        foreach ($this->objects as $object) {
            if ($object->hasBeenLoadedFromDb()) {
                if ($object->hasBeenModified()) {
                    $mod[$object->id] = $object->object_name;
                } else {
                    $same[$object->id] = $object->object_name;
                }
            } else {
                $new[$object->id] = $object->object_name;
            }
        }

        $existing = $dba->fetchPairs(
            $dba->select()->from('object', ['id', 'object_name'])
        );

        foreach ($existing as $id => $name) {
            if (! array_key_exists($id, $fetched)) {
                $del[$id] = $name;
                // $this->objects[$id]->delete();
                unset($this->objects[$id]);
            }
        }

        if (! empty($del)) {
            $dba->update(
                'object',
                ['parent_id' => null],
                $dba->quoteInto('parent_id IN (?)', array_keys($del))
            );
            $dba->delete(
                'object',
                $dba->quoteInto('id IN (?)', array_keys($del))
            );
        }

        printf("%d new: %s\n", count($new), implode(', ', $new));
        printf("%d mod: %s\n", count($mod), implode(', ', $mod));
        foreach ($mod as $id => $name) {
            printf("%s has been modified:\n", $name);
            print_r($this->objects[$id]->getModifiedProperties());
        }
        printf("%d del: %s\n", count($del), implode(', ', $del));
        printf("%d unmodified\n", count($same));

        foreach ($this->objects as $object) {
            $object->store();
        }
        $dba->commit();
        Benchmark::measure(sprintf('Committed %d objects', count($this->objects)));
        $this->lastLookup = time();

        return $this;
    }

    public function fetchNames()
    {
        return $this->api->propertyCollector()->collectProperties(
            $this->prepareNameSpecSet()
        );
    }

    protected function prepareNameSpecSet()
    {
        $types = [
            'Datacenter',
            'Datastore',
            'Folder',
            'ResourcePool',
            'HostSystem',
            'ComputeResource',
            'ClusterComputeResource',
            'StoragePod',
            'VirtualMachine',
        ];
        $pathSet = ['name', 'parent', 'overallStatus'];

        $propSet = [];
        foreach ($types as $type) {
            $propSet[] = [
                'type' => $type,
                'all' => 0,
                'pathSet' => $pathSet
            ];
        }
        return [
            'propSet' => $propSet,
            'objectSet' => [
                'obj'  => $this->api->getServiceInstance()->rootFolder,
                'skip' => false,
                'selectSet' => (new FullSelectSet())->toArray(),
            ]
        ];
    }

    public function dump()
    {
        print_r($this->idToName);
        print_r($this->idToParent);
        print_r($this->idToType);
    }

    /**
     * @return int
     */
    public function getCacheTimeout()
    {
        return $this->cacheTimeout;
    }

    /**
     * @param int $cacheTimeout
     * @return $this;
     */
    public function setCacheTimeout($cacheTimeout)
    {
        $this->cacheTimeout = $cacheTimeout;
        return $this;
    }

    /**
     * @return $this
     */
    protected function requireFreshMaps()
    {
        if ($this->cacheTimeout === null || time() - $this->cacheTimeout > $this->lastLookup) {
            $this->refresh();
        }

        return $this;
    }

    public function __destruct()
    {
        unset($this->api);
    }
}
