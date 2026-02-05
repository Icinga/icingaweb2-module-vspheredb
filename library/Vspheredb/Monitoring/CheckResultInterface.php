<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\CheckPluginState;

interface CheckResultInterface
{
    public function getState(): CheckPluginState;

    public function getOutput(): string;
}
