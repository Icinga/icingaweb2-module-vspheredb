<?php

namespace Icinga\Module\Vspheredb\Polling;

use gipfl\Json\JsonSerialization;
use gipfl\Json\JsonString;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;

class ServerSet implements JsonSerialization
{
    protected $servers = [];

    /**
     * ServerSet constructor.
     * @param ServerInfo[] $servers
     */
    public function __construct($servers = [])
    {
        foreach ($servers as $server) {
            $this->addServer($server);
        }
    }

    public static function fromSerialization($any)
    {
        $self = new static();
        foreach ($any as $key => $server) {
            $info = ServerInfo::fromSerialization($server);
            // This doesn't fail - yet
            assert($key === $info->getIdentifier());
            $self->addServer($info);
        }

        return $self;
    }

    public function addServer(ServerInfo $server)
    {
        $this->servers[$server->getIdentifier()] = $server;
        ksort($this->servers);
    }

    /**
     * @return ServerInfo[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param VCenterServer[] $servers
     * @return static
     */
    public static function fromServers($servers)
    {
        $serverInfo = [];
        foreach ($servers as $server) {
            $serverInfo[] = ServerInfo::fromServer($server);
        }

        return new static($serverInfo);
    }

    public function equals(ServerSet $set)
    {
        return JsonString::encode($set) === JsonString::encode($this);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) $this->servers;
    }
}
