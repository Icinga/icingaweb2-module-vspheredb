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

    private $lastLookup;

    /** @var int */
    private $cacheTimeout = 120;

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
