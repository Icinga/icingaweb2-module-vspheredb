<?php

namespace Icinga\Module\Vsphere;

use Icinga\Application\Benchmark;
use Icinga\Module\Vsphere\ManagedObject\FullTraversal;

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

    /**
     * IdLookup constructor.
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param string$id
     * @return string|null
     */
    public function getNameForId($id)
    {
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
        array_map($path, array($this, 'getNameForId'));
        return implode($separator, $path);
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
     * Refresh our internal ID cache
     *
     * @return $this
     */
    public function refresh()
    {
        $this->api->login();
        Benchmark::measure('Ready to fetch id/name/parent list');
        $all = FullTraversal::fetchNames($this->api);
        Benchmark::measure(sprintf("Got id/name/parent for %d objects", count($all)));
        foreach ($all as $obj) {
            $idToName[$obj->id] = $obj->name;
            $idToType[$obj->id] = $obj->type;
            $idToParent[$obj->id] = $obj->parent;
        }

        return $this;
    }


    public function __destruct()
    {
        unset($this->api);
    }
}
