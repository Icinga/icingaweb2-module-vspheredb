<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Translation\StaticTranslator;
use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\Monitoring\Health\ApiConnectionInfo;
use Icinga\Module\Vspheredb\Monitoring\Health\ServerConnectionInfo;
use Icinga\Module\Vspheredb\Polling\ApiConnection;

class ConnectionState
{
    /** @var array */
    protected $daemonApiConnections;

    /** @var Adapter|\Zend_Db_Adapter_Abstract */
    protected $db;

    /**
     * @param ApiConnectionInfo[] $daemonApiConnections
     * @param Adapter|\Zend_Db_Adapter_Abstract $db
     */
    public function __construct(array $daemonApiConnections, $db)
    {
        $this->daemonApiConnections = [];
        foreach ($daemonApiConnections as $connection) {
            $this->daemonApiConnections[] = ApiConnectionInfo::fromSerialization($connection);
        }
        $this->db = $db;
    }

    /**
     * @return array<int, array<int, ServerConnectionInfo>>
     */
    public function getConnectionsByVCenter(): array
    {
        $connectionsByVCenter = $this->getConfiguredServersByVCenter();
        foreach ($this->daemonApiConnections as $info) {
            if (isset($connectionsByVCenter[$info->vCenterId])) {
                if (isset($connectionsByVCenter[$info->vCenterId][$info->serverId])) {
                    $connectionsByVCenter[$info->vCenterId][$info->serverId]->setApiConnection($info);
                } else {
                    $connectionsByVCenter[$info->vCenterId][$info->serverId] = new ServerConnectionInfo(
                        $info->server,
                        false,
                        false,
                        $info
                    );
                }
            } else {
                $connectionsByVCenter[$info->vCenterId] = [$info->serverId => $info];
            }
        }

        return $connectionsByVCenter;
    }

    protected function getConfiguredServersByVCenter(): array
    {
        $db = $this->db;
        $result = [];
        foreach ($db->fetchAll(
            $db->select()->from('vcenter_server', [
                'id',
                'vcenter_id',
                'host',
                'enabled'
            ])
        ) as $server) {
            if (! isset($result[$server->vcenter_id])) {
                $result[$server->vcenter_id] = [];
            }
            $result[$server->vcenter_id][$server->id] =  new ServerConnectionInfo(
                $server->host,
                $server->enabled === 'y',
                true
            );
        }

        return $result;
    }

    public static function describe(ServerConnectionInfo $info): string
    {
        $t = StaticTranslator::get();
        $state = $info->getState();
        $label = $info->serverName;
        $lastError = $info->apiConnection ? $info->apiConnection->lastErrorMessage : null;
        if ($lastError) {
            $lastError = ": $lastError";
        }
        switch ($state) {
            case 'unknown':
                return sprintf(
                    $t->translate('Connections to %s have been enabled, but none is currently active'),
                    $label
                ) . $lastError;
            case 'disabled':
                return sprintf(
                    $t->translate('Connections to %s have been disabled'),
                    $label
                );
            case ApiConnection::STATE_CONNECTED:
                return sprintf(
                    $t->translate('API connection with %s is fine'),
                    $label
                );
            case ApiConnection::STATE_LOGIN:
                return sprintf(
                    $t->translate('Trying to log in to %s'),
                    $label
                ) . $lastError;
            case ApiConnection::STATE_INIT:
                return sprintf(
                    $t->translate('Initializing API connection with %s'),
                    $label
                ) . $lastError;
            case ApiConnection::STATE_FAILING:
                return sprintf(
                    $t->translate('API connection with %s is failing'),
                    $label
                ) . $lastError;
            case ApiConnection::STATE_STOPPING:
                return sprintf(
                    $t->translate('Stopping API connection with %s'),
                    $label
                ) . $lastError;
            default:
                return $t->translate("Unknown API connection state: $state") . $lastError;
        }
    }

    public static function describeNoServer()
    {
        return StaticTranslator::get()->translate('There is no configured server for this vCenter');
    }
}
