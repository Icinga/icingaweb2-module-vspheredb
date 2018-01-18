<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Util;

class Vcenter extends BaseDbObject
{
    protected $table = 'vcenter';

    protected $keyName = 'instance_uuid';

    protected $autoincKeyName = 'id';

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
}
