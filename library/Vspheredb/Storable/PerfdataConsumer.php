<?php

namespace Icinga\Module\Vspheredb\Storable;

use gipfl\Json\JsonString;
use gipfl\ZfDbStore\DbStorable;
use gipfl\ZfDbStore\DbStorableInterface;

class PerfdataConsumer implements DbStorableInterface
{
    use DbStorable;

    protected $tableName = 'perfdata_consumer';

    protected $keyProperty = 'uuid';

    protected $defaultProperties = [
        'uuid'             => null,
        'name'             => null,
        'implementation'   => null,
        'settings'         => null,
        'enabled'          => null,
    ];

    public function settings()
    {
        $settings = $this->get('settings');
        if ($settings === null) {
            return null;
        }

        return JsonString::decode($settings);
    }
}
