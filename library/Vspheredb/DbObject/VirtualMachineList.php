<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VirtualMachineList extends MoRefList
{
    public const LIST_TABLE_NAME = 'vm_list';

    public const MEMBER_TABLE_NAME = 'vm_list_member';
}
