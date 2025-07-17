<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @method Db getConnection()
 */
class VCenter extends BaseDbObject
{
    protected $table = 'vcenter';

    protected $keyName = 'instance_uuid';

    protected $autoincKeyName = 'id';

    protected $defaultProperties = [
        'id'                      => null,
        'instance_uuid'           => null,
        'trust_store_id'          => null,
        'name'                    => null,
        'api_name'                => null,
        'version'                 => null,
        'os_type'                 => null,
        'api_type'                => null,
        'api_version'             => null,
        'build'                   => null,
        'vendor'                  => null,
        'product_line'            => null,
        'license_product_name'    => null,
        'license_product_version' => null,
        'locale_build'            => null,
        'locale_version'          => null,
    ];

    protected $propertyMap = [
        'instanceUuid'          => 'instance_uuid',
        'name'                  => 'api_name',
        'version'               => 'version',
        'osType'                => 'os_type',
        'apiType'               => 'api_type',
        'apiVersion'            => 'api_version',
        'build'                 => 'build',
        'vendor'                => 'vendor',
        'productLineId'         => 'product_line',
        'licenseProductName'    => 'license_product_name',
        'licenseProductVersion' => 'license_product_version',
        'localeBuild'           => 'locale_build',
        'localeVersion'         => 'locale_version',
    ];

    public function getFullName()
    {
        return sprintf(
            '%s %s build-%s',
            \preg_replace('/^VMware /', '', $this->get('api_name')),
            $this->get('version'),
            $this->get('build')
        );
    }

    public function isHostAgent()
    {
        return $this->get('api_type') === 'HostAgent';
    }

    public function isVirtualCenter()
    {
        return $this->get('api_type') === 'VirtualCenter';
    }

    // TODO: Settle with one or the other. This should better give a UUID object
    public function getUuid()
    {
        return $this->get('instance_uuid');
    }

    public function getBinaryUuid()
    {
        return $this->get('instance_uuid');
    }

    public static function loadWithUuid(string $uuid, Db $connection)
    {
        if (strlen($uuid) === 16) {
            $uuid = Uuid::fromBytes($uuid);
        } else {
            $uuid = Uuid::fromString($uuid);
        }

        return static::load($uuid->getBytes(), $connection);
    }

    /**
     * @param bool $enabled
     * @return VCenterServer
     * @throws NotFoundError
     */
    public function getFirstServer($enabled = true, $required = true)
    {
        $db = $this->getConnection()->getDbAdapter();
        $query = $db->select()
            ->from('vcenter_server')
            ->where('vcenter_id = ?', $this->get('id'))
            ->limit(1);
        if ($enabled) {
            $query->where('enabled = ?', 'y');
        }
        $serverId = $db->fetchOne($query);
        if ($serverId) {
            return VCenterServer::loadWithAutoIncId($serverId, $this->getConnection());
        } elseif ($enabled) {
            $serverId = $db->fetchOne(
                $db->select()
                    ->from('vcenter_server')
                    ->where('vcenter_id = ?', $this->get('id'))
                    ->limit(1)
            );
            if ($serverId) {
                throw new NotFoundError(
                    'All server connections configured for this vCenter have been disabled'
                );
            } else {
                throw new NotFoundError(
                    'Found no server for vCenterId=' . $this->get('id')
                );
            }
        } elseif ($required) {
            throw new NotFoundError(
                'Found no server for vCenterId=' . $this->get('id')
            );
        } else {
            return null;
        }
    }

    public function makeBinaryGlobalUuid($moRefId)
    {
        if ($moRefId instanceof ManagedObjectReference || $moRefId instanceof \stdClass) {
            return $this->makeBinaryGlobalMoRefUuid($moRefId);
        } elseif (is_string($moRefId)) {
            return Uuid::uuid5(Uuid::fromBytes($this->get('uuid')), $moRefId)->getBytes();
        } else {
            throw new \RuntimeException('MoRef expected, got ' . gettype($moRefId));
        }
    }

    /**
     * @param ManagedObjectReference|\stdClass $moRef
     * @return string
     */
    public function makeBinaryGlobalMoRefUuid($moRef): string
    {
        return $this->makeBinaryGlobalMoRefUuidObject($moRef)->getBytes();
    }

    /**
     * @param ManagedObjectReference|\stdClass $moRef
     */
    public function makeBinaryGlobalMoRefUuidObject($moRef): UuidInterface
    {
        if ($moRef instanceof \stdClass) {
            $moRef = ManagedObjectReference::fromSerialization($moRef);
        }

        return Uuid::uuid5(Uuid::fromBytes($this->get('uuid')), $moRef->_);
    }

    /**
     * @param $value
     * @codingStandardsIgnoreStart
     */
    public function setInstance_uuid($value)
    {
        // @codingStandardsIgnoreEnd
        if (strlen($value) > 16) {
            $this->reallySet('instance_uuid', Uuid::fromString($value)->getBytes());
        } else {
            $this->reallySet('instance_uuid', $value);
        }
    }
}
