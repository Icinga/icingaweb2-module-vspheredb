<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingCategory;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingCategorySyncTask extends TaggingSyncTask
{
    protected string $label = 'Tag Categories';

    protected string $tableName = TaggingCategory::TABLE;

    protected string $objectClass = TaggingCategory::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllCategories();
    }
}
