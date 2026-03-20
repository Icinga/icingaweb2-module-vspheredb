<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingTag;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingTagSyncTask extends TaggingSyncTask
{
    protected string $label = 'Tags';

    protected string $tableName = TaggingTag::TABLE;

    protected string $objectClass = TaggingTag::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllTags();
    }
}
