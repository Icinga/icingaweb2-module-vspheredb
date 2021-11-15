<?php

namespace Icinga\Module\Vspheredb\Storable;

use gipfl\Json\JsonString;
use gipfl\ZfDbStore\DbStorable;
use gipfl\ZfDbStore\DbStorableInterface;
use gipfl\ZfDbStore\ZfDbStore;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Ramsey\Uuid\Uuid;

class PerfdataSubscription implements DbStorableInterface
{
    use DbStorable {
        set as parentSet;
    }

    protected $tableName = 'perfdata_subscription';

    protected $keyProperty = 'uuid';

    protected $defaultProperties = [
        'uuid'          => null,
        'consumer_uuid' => null,
        'vcenter_uuid'  => null,
        'settings'      => null,
        'enabled'       => null,
    ];

    public function set($property, $value)
    {
        if ($property === 'consumer') {
            $property = 'consumer_uuid';
            $value = Uuid::fromString($value)->getBytes();
        }

        return $this->parentSet($property, $value);
    }

    public function settings()
    {
        $settings = $this->get('settings');
        if ($settings === null) {
            return null;
        }

        return JsonString::decode($settings);
    }

    /**
     * @param VCenter $vCenter
     * @return PerfdataSubscription|null
     */
    public static function optionallyLoadForVCenter(VCenter $vCenter, ZfDbStore $store)
    {
        $db = $store->getDb();
        $uuids = $db->fetchCol(
            $db->select()
                ->from('perfdata_subscription')
                ->where('vcenter_uuid = ?', $vCenter->get('instance_uuid'))
        );
        if (empty($uuids)) {
            return null;
        }

        if (count($uuids) > 1) {
            throw new \RuntimeException('More then one consumer per vCenter is currently not supported');
        }

        return static::load($store, $uuids[0]);
    }
}
