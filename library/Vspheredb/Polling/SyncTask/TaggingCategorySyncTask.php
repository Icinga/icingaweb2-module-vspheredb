<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingCategory;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingCategorySyncTask extends TaggingSyncTask
{
    protected $label = 'Tag Categories';
    protected $tableName = TaggingCategory::TABLE;
    protected $objectClass = TaggingCategory::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllCategories();
    }
}
