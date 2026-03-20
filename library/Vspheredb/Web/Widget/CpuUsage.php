<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;

class CpuUsage extends UsageBar
{
    public function __construct(int|float|null $used, int|float|null $capacity)
    {
        parent::__construct($used, $capacity);
        $this->formatter = Format::mhz(...);
    }
}
