<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Translation\StaticTranslator;
use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Ramsey\Uuid\Uuid;

class ConnectionState
{
    protected const STATE_MAP = [
        ApiConnection::STATE_INIT      => 'WARNING',
        ApiConnection::STATE_LOGIN     => 'WARNING',
        ApiConnection::STATE_CONNECTED => 'OK',
        ApiConnection::STATE_FAILING   => 'CRITICAL',
        ApiConnection::STATE_STOPPED   => 'WARNING',
        ApiConnection::STATE_STOPPING  => 'WARNING',
        'unknown' => 'UNKNOWN',
    ];
    /** @var array */
    protected $daemonApiConnections;
    /**
     * @var Adapter|\Zend_Db_Adapter_Abstract
     */
    protected $db;

    public function __construct($daemonApiConnections, $db)
    {
        $this->daemonApiConnections = (array) $daemonApiConnections;
        $this->db = $db;
    }

    public function getVCenters(): array
    {
        $columns = [
            'uuid'       => 'vc.instance_uuid',
            'vcenter_id' => 'vc.id',
            'name'       => 'vc.name',
            'software_name'    => 'vc.api_name',
            'software_version' => 'vc.version',
        ];

        $result = [];
        $rows = $this->db->fetchAll($this->db->select()->from(['vc' => 'vcenter'], $columns)->order('name'));
        foreach ($rows as $row) {
            $result[$row->vcenter_id] = $row;
            $row->uuid = Uuid::fromBytes(DbUtil::binaryResult($row->uuid))->toString();
            $row->software = \sprintf(
                '%s (%s)',
                \preg_replace('/^VMware /', '', $row->software_name),
                $row->software_version
            );
        }

        return $result;
    }

    public function getConnectionsByVCenter()
    {
        $connectionsByVCenter = $this->getConfiguredServersByVCenter();
        foreach ($this->daemonApiConnections as $id => $connection) {
            if (isset($connectionsByVCenter[$connection->vcenter_id])) {
                $connectionsByVCenter[$connection->vcenter_id][$connection->server_id]->state = $connection->state;
            } else {
                $connectionsByVCenter[$connection->vcenter_id] = [$connection->server_id => (object) [
                    'state'  => $connection->state,
                    'server' => $connection->server,
                ]];
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
            $result[$server->vcenter_id][$server->id] = (object) [
                'server' => $server->host,
                'enabled' => $server->enabled === 'y',
                'state'   => $server->enabled === 'n' ? 'disabled' : 'unknown',
            ];
        }

        return $result;
    }

    public static function describe(string $state, string $label): string
    {
        $t = StaticTranslator::get();
        switch ($state) {
            case 'unknown':
                return sprintf(
                    $t->translate('Connections to %s have been enabled, but none is currently active'),
                    $label
                );
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
                );
            case ApiConnection::STATE_INIT:
                return sprintf(
                    $t->translate('Initializing API connection with %s'),
                    $label
                );
            case ApiConnection::STATE_FAILING:
                return sprintf(
                    $t->translate('API connection with %s is failing'),
                    $label
                );
            case ApiConnection::STATE_STOPPING:
                return sprintf(
                    $t->translate('Stopping API connection with %s'),
                    $label
                );
            default:
                return $t->translate("Unknown API connection state: $state");
        }
    }

    public static function describeNoServer()
    {
        return StaticTranslator::get()->translate('There is no configured server for this vCenter');
    }

    public static function getIcingaState(string $apiState): string
    {
        if (isset(self::STATE_MAP[$apiState])) {
            return self::STATE_MAP[$apiState];
        }

        throw new \InvalidArgumentException("'$apiState' is not a known ApiConnection state");
    }
}
