<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;

class CpuUsage extends UsageBar
{
    protected $formatter = [Format::class, 'mhz'];
}
