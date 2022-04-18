<?php

namespace Icinga\Module\Vspheredb\Monitoring;

interface CheckResultInterface
{
    public function getState(): CheckPluginState;

    public function getOutput(): string;
}
