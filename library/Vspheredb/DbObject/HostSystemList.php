<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostSystemList extends MoRefList
{
    public const LIST_TABLE_NAME = 'host_list';

    public const MEMBER_TABLE_NAME = 'host_list_member';
}
