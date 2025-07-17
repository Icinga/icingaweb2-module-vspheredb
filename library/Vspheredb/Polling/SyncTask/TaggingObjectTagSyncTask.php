<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingObjectTag;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingObjectTagSyncTask extends TaggingSyncTask
{
    protected $label = 'Object Tags';
    protected $tableName = TaggingObjectTag::TABLE;
    protected $objectClass = TaggingObjectTag::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllAssignments();
    }
}
