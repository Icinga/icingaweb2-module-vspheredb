<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;
use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class VCenter extends BaseDbObject
{
    protected $table = 'vcenter';

    protected $keyName = 'instance_uuid';

    protected $autoincKeyName = 'id';

    /** @var Api */
    private $api;

    protected $defaultProperties = [
        'id'                      => null,
        'instance_uuid'           => null,
        'trust_store_id'          => null,
        'name'                    => null,
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
        'name'                  => 'name',
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
            $this->get('api_type'),
            $this->get('version'),
            $this->get('build')
        );
    }

    // TODO: Settle with one or the other
    public function getUuid()
    {
        return $this->get('instance_uuid');
    }

    /**
     * @return Api
     * @throws \Icinga\Exception\NotFoundError
     */
    public function getApi()
    {
        if ($this->api === null) {
            $this->api = $this->createNewApiConnection();
        }

        return $this->api;
    }

    /**
     * @return Api
     * @throws \Icinga\Exception\NotFoundError
     */
    public function createNewApiConnection()
    {

        return Api::forServer($this->getFirstServer());
    }

    /**
     * @return VCenterServer
     * @throws \Icinga\Exception\NotFoundError
     */
    public function getFirstServer()
    {
        $db = $this->getConnection()->getDbAdapter();
        $serverId = $db->fetchOne(
            $db->select()
                ->from('vcenter_server')
                ->where('vcenter_id = ?', $this->get('id'))
                ->limit(1)
        );

        return VCenterServer::loadWithAutoIncId($serverId, $this->getConnection());
    }

    public function makeBinaryGlobalUuid($moRefId)
    {
        if ($moRefId instanceof ManagedObjectReference) {
            return sha1($this->get('uuid') . $moRefId->_, true);
        } else {
            return sha1($this->get('uuid') . $moRefId, true);
        }
    }

    /**
     * @param $value
     * @codingStandardsIgnoreStart
     */
    public function setInstance_uuid($value)
    {
        // @codingStandardsIgnoreEnd
        if (strlen($value) > 16) {
            $value = Util::uuidToBin($value);
        }

        $this->reallySet('instance_uuid', $value);
    }

    /**
     * Just to help the IDE
     *
     * @return Db
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Hint: this also updates the vCenter.
     *
     * @param Api $api
     * @param Db $db
     * @return VCenter
     * @throws \Icinga\Exception\AuthenticationException
     * @throws \Icinga\Exception\NotFoundError
     * @throws \Icinga\Module\Vspheredb\Exception\DuplicateKeyException
     */
    public static function fromApi(Api $api, Db $db)
    {
        $about = $api->getAbout();
        $uuid = $api->getBinaryUuid();
        if (VCenter::exists($uuid, $db)) {
            $vCenter = VCenter::load($uuid, $db);
        } else {
            $vCenter = VCenter::create([], $db);
        }

        // Workaround for ESXi, about has no instanceUuid
        $about->instanceUuid = $uuid;
        $vCenter->setMapped($about, $vCenter);

        if ($vCenter->hasBeenModified()) {
            if ($vCenter->hasBeenLoadedFromDb()) {
                Logger::info('vCenter has been modified');
            } else {
                Logger::info('vCenter has been created');
            }

            $vCenter->store();
        } else {
            Logger::info("vCenter hasn't been changed");
        }

        return $vCenter;
    }
}
