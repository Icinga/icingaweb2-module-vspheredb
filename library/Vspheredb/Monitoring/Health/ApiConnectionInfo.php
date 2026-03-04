<?php

namespace Icinga\Module\Vspheredb\Monitoring\Health;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Icinga\Module\Vspheredb\Polling\ServerInfo;
use InvalidArgumentException;
use stdClass;

class ApiConnectionInfo implements JsonSerialization
{
    protected const STATE_MAP = [
        ApiConnection::STATE_INIT      => 'WARNING',
        ApiConnection::STATE_LOGIN     => 'WARNING',
        ApiConnection::STATE_CONNECTED => 'OK',
        ApiConnection::STATE_FAILING   => 'CRITICAL',
        ApiConnection::STATE_STOPPED   => 'WARNING',
        ApiConnection::STATE_STOPPING  => 'WARNING',
        'unknown'                      => 'CRITICAL'
    ];

    /** @var string */
    public string $state;

    /** @var string */
    public string $server;

    /** @var int */
    public int $serverId;

    /** @var int */
    public int $vCenterId;

    /** @var ?int */
    public ?int $connectionId = null;

    /** @var ?string */
    public ?string $lastErrorMessage = null;

    /**
     * @param ApiConnection $connection
     *
     * @return ApiConnectionInfo
     */
    public static function fromConnectionInfo(ApiConnection $connection): ApiConnectionInfo
    {
        $server = $connection->getServerInfo();
        $info = new ApiConnectionInfo(
            $connection->getState(),
            $server->getIdentifier(),
            (int) $server->get('id'),
            (int) $server->get('vcenter_id'),
            $connection->getLastErrorMessage()
        );
        $info->connectionId = spl_object_id($connection);

        return $info;
    }

    public static function fromSerialization(mixed $any): ApiConnectionInfo
    {
        $self = new ApiConnectionInfo(
            $any->state,
            $any->server,
            $any->serverId,
            $any->vCenterId,
            $any->lastErrorMessage ?? null
        );

        if (isset($any->connectionId)) {
            $self->connectionId = $any->connectionId;
        }

        return $self;
    }

    /**
     * @param ServerInfo $server
     * @param string $message
     *
     * @return ApiConnectionInfo
     */
    public static function failingConnectionForServer(ServerInfo $server, string $message): ApiConnectionInfo
    {
        return new ApiConnectionInfo(
            ApiConnection::STATE_FAILING,
            $server->getIdentifier(),
            $server->getServerId(),
            $server->getVCenterId(),
            $message
        );
    }

    /**
     * @return string
     */
    public function getIcingaState(): string
    {
        return self::STATE_MAP[$this->state];
    }

    /**
     * @return stdClass
     */
    public function jsonSerialize(): stdClass
    {
        $self = (object) [
            'state' => $this->state,
            'server' => $this->server,
            'serverId' => $this->serverId,
            'vCenterId' => $this->vCenterId
        ];

        if ($this->lastErrorMessage) {
            $self->lastErrorMessage = $this->lastErrorMessage;
        }
        if ($this->connectionId) {
            $self->connectionId = $this->connectionId;
        }

        return $self;
    }

    /**
     * @param string $state
     * @param string $server
     * @param int $serverId
     * @param int $vCenterId
     * @param ?string $lastErrorMessage
     *
     * @throws InvalidArgumentException
     */
    protected function __construct(
        string $state,
        string $server,
        int $serverId,
        int $vCenterId,
        ?string $lastErrorMessage = null
    ) {
        if (!isset(self::STATE_MAP[$state])) {
            throw new InvalidArgumentException("'$state' is not a known ApiConnection state");
        }

        $this->state = $state;
        $this->server = $server;
        $this->serverId = $serverId;
        $this->vCenterId = $vCenterId;
        $this->lastErrorMessage = $lastErrorMessage;
    }
}
