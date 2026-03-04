<?php

namespace Icinga\Module\Vspheredb\Monitoring\Health;

/**
 * We might have:
 *
 * - connections for servers, which are no longer configured
 * - connections for disabled servers
 * - configured servers with no connection
 */
class ServerConnectionInfo
{
    /** @var ?ApiConnectionInfo */
    public ?ApiConnectionInfo $apiConnection = null;

    /** @var bool */
    public bool $enabled;

    /** @var string */
    public string $serverName;

    /** @var bool */
    protected bool $configured;

    /**
     * @param string $serverName
     * @param bool $enabled
     * @param bool $configured
     * @param ApiConnectionInfo|null $apiConnection
     */
    public function __construct(
        string $serverName,
        bool $enabled,
        bool $configured,
        ?ApiConnectionInfo $apiConnection = null
    ) {
        $this->serverName = $serverName;
        $this->enabled = $enabled;
        $this->apiConnection = $apiConnection;
        $this->configured = $configured;
    }

    /**
     * @param ?ApiConnectionInfo $apiConnection
     *
     * @return void
     */
    public function setApiConnection(?ApiConnectionInfo $apiConnection): void
    {
        $this->apiConnection = $apiConnection;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        if ($this->enabled) {
            if ($this->apiConnection) {
                return $this->apiConnection->state;
            }

            return 'unknown';
        }

        return 'disabled';
    }

    /**
     * @return string
     */
    public function getIcingaState(): string
    {
        if ($this->enabled) {
            if ($this->apiConnection) {
                return $this->apiConnection->getIcingaState();
            }

            return 'UNKNOWN';
        }

        return 'UNKNOWN';
    }
}
