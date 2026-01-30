<?php

namespace Icinga\Module\Vspheredb\Storable;

use gipfl\Json\JsonString;
use gipfl\ZfDbStore\DbStorable;
use gipfl\ZfDbStore\DbStorableInterface;

class PerfdataConsumer implements DbStorableInterface
{
    use DbStorable;

    protected string $tableName = 'perfdata_consumer';

    protected string $keyProperty = 'uuid';

    protected array $defaultProperties = [
        'uuid'             => null,
        'name'             => null,
        'implementation'   => null,
        'settings'         => null,
        'enabled'          => null,
    ];

    public function settings(): mixed
    {
        $settings = $this->get('settings');
        if ($settings === null) {
            return null;
        }

        return JsonString::decode($settings);
    }
}
