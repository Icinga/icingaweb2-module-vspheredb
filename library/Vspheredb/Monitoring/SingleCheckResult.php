<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\CheckPluginState;

class SingleCheckResult implements CheckResultInterface
{
    protected CheckPluginState $state;

    protected string $output;

    public function __construct(CheckPluginState $state, string $output)
    {
        $this->state = $state;
        $this->output = $output;
    }

    public function getState(): CheckPluginState
    {
        return $this->state;
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
