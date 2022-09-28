<?php

namespace Icinga\Module\Vspheredb\Polling;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;

class ServerSet implements JsonSerialization
{
    /** @var array<int, ServerInfo> */
    protected $servers = [];

    /**
     * ServerSet constructor.
     * @param ServerInfo[] $servers
     */
    public function __construct(array $servers = [])
    {
        foreach ($servers as $server) {
            $this->addServer($server);
        }
    }

    public static function fromSerialization($any): ServerSet
    {
        $self = new static();
        foreach ($any as $server) {
            $info = ServerInfo::fromSerialization($server);
            $self->addServer($info);
        }

        return $self;
    }

    public function listServerIds(): array
    {
        return array_keys($this->servers);
    }

    public function addServer(ServerInfo $server)
    {
        $this->servers[$server->getServerId()] = $server;
        ksort($this->servers);
    }

    public function getServer(int $serverId): ServerInfo
    {
        return $this->servers[$serverId];
    }

    /**
     * @return ServerInfo[]
     */
    public function getServers(): array
    {
        return array_values($this->servers);
    }

    /**
     * @param VCenterServer[] $servers
     * @return static
     */
    public static function fromServers(array $servers): ServerSet
    {
        $serverInfo = [];
        foreach ($servers as $server) {
            $serverInfo[] = ServerInfo::fromServer($server);
        }

        return new static($serverInfo);
    }

    public function equals(ServerSet $set): bool
    {
        if ($set->listServerIds() !== $this->listServerIds()) {
            return false;
        }

        foreach ($this->servers as $id => $server) {
            if (! $set->getServer($id)->equals($server)) {
                return false;
            }
        }

        return true;
    }

    public function jsonSerialize(): array
    {
        // Hint: returning $this->servers would ship in a JSON object, keys are not sequential
        return array_values($this->servers);
    }
}
