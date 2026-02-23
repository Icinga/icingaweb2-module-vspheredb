<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\TaggingTag;
use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

class TaggingTagSyncTask extends TaggingSyncTask
{
    protected $label = 'Tags';
    protected $tableName = TaggingTag::TABLE;
    protected $objectClass = TaggingTag::class;

    public function run(RestApi $api): PromiseInterface
    {
        return $api->fetchAllTags();
    }
}
