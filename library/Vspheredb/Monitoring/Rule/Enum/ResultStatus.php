<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

enum ResultStatus: string
{
    case CREATED = 'created';
    case MODIFIED = 'modified';
    case UNMODIFIED = 'unmodified';
    case DELETED = 'deleted';
}
