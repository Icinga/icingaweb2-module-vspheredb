<?php

namespace Icinga\Module\Vspheredb\Monitoring;

class SingleCheckResult implements CheckResultInterface
{
    /** @var CheckPluginState */
    protected $state;

    /** @var string */
    protected $output;

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
