<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingObjectTag;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingObjectTagSyncTask extends TaggingSyncTask
{
    protected string $label = 'Object Tags';

    protected string $tableName = TaggingObjectTag::TABLE;

    protected string $objectClass = TaggingObjectTag::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllAssignments();
    }
}
