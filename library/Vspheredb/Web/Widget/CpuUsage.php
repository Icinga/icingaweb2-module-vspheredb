<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;

class CpuUsage extends UsageBar
{
    // TODO: change once enforcing PHP 5.6
    // protected $fomatter = [Format::class, 'mhz'];
    protected $formatter = [
        'Icinga\\Module\\Vspheredb\\Format',
        'mhz'
    ];
}
