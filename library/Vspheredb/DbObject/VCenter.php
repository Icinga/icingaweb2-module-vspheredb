<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use stdClass;

/**
 * @method Db getConnection()
 */
class VCenter extends BaseDbObject
{
    protected ?string $table = 'vcenter';

    protected string|array|null $keyName = 'instance_uuid';

    protected ?string $autoincKeyName = 'id';

    protected ?array $defaultProperties = [
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
        'locale_version'          => null
    ];

    protected array $propertyMap = [
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
        'localeVersion'         => 'locale_version'
    ];

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return sprintf(
            '%s %s build-%s',
            preg_replace('/^VMware /', '', $this->get('api_name')),
            $this->get('version'),
            $this->get('build')
        );
    }

    /**
     * @return bool
     */
    public function isHostAgent(): bool
    {
        return $this->get('api_type') === 'HostAgent';
    }

    /**
     * @return bool
     */
    public function isVirtualCenter(): bool
    {
        return $this->get('api_type') === 'VirtualCenter';
    }


    /**
     * TODO: Settle with one or the other. This should better give a UUID object
     *
     * @return mixed
     */
    public function getUuid(): mixed
    {
        return $this->get('instance_uuid');
    }

    /**
     * @return mixed
     */
    public function getBinaryUuid(): mixed
    {
        return $this->get('instance_uuid');
    }

    public static function loadWithUuid(string $uuid, Db $connection): static
    {
        $uuid = strlen($uuid) === 16 ? Uuid::fromBytes($uuid) : Uuid::fromString($uuid);

        return static::load($uuid->getBytes(), $connection);
    }

    /**
     * @param bool $enabled
     * @param bool $required
     *
     * @return VCenterServer|null
     *
     * @throws NotFoundError
     */
    public function getFirstServer(bool $enabled = true, bool $required = true): ?VCenterServer
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
                throw new NotFoundError('All server connections configured for this vCenter have been disabled');
            }

            throw new NotFoundError('Found no server for vCenterId=' . $this->get('id'));
        } elseif ($required) {
            throw new NotFoundError('Found no server for vCenterId=' . $this->get('id'));
        }

        return null;
    }

    /**
     * @param mixed $moRefId
     *
     * @return string
     */
    public function makeBinaryGlobalUuid(mixed $moRefId): string
    {
        if ($moRefId instanceof ManagedObjectReference || $moRefId instanceof stdClass) {
            return $this->makeBinaryGlobalMoRefUuid($moRefId);
        }

        if (is_string($moRefId)) {
            return Uuid::uuid5(Uuid::fromBytes($this->get('uuid')), $moRefId)->getBytes();
        }

        throw new RuntimeException('MoRef expected, got ' . gettype($moRefId));
    }

    /**
     * @param stdClass|ManagedObjectReference $moRef
     *
     * @return string
     */
    public function makeBinaryGlobalMoRefUuid(stdClass|ManagedObjectReference $moRef): string
    {
        return $this->makeBinaryGlobalMoRefUuidObject($moRef)->getBytes();
    }

    /**
     * @param stdClass|ManagedObjectReference $moRef
     *
     * @return UuidInterface
     */
    public function makeBinaryGlobalMoRefUuidObject(stdClass|ManagedObjectReference $moRef): UuidInterface
    {
        if ($moRef instanceof stdClass) {
            $moRef = ManagedObjectReference::fromSerialization($moRef);
        }

        return Uuid::uuid5(Uuid::fromBytes($this->get('uuid')), $moRef->_);
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setInstance_uuid(string $value): void // phpcs:ignore
    {
        $this->reallySet('instance_uuid', strlen($value) > 16 ? Uuid::fromString($value)->getBytes() : $value);
    }
}
