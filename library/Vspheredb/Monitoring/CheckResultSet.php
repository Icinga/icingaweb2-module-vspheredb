<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Icinga\Module\Director\CheckPlugin\CheckResult;

class CheckResultSet implements CheckResultInterface
{
    const NUMERATION_PREFIX = ' \\_ ';

    /** @var string */
    protected $name;

    /** @var CheckResultInterface[] */
    protected $results = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addResult(CheckResultInterface $result)
    {
        $this->results[] = $result;
    }

    public function getState(): CheckPluginState
    {
        $state = new CheckPluginState();
        foreach ($this->results as $result) {
            $state->raiseState($result->getState());
        }

        return $state;
    }

    public function isEmpty(): bool
    {
        return empty($this->results);
    }

    public function getOutput($prefix = ''): string
    {
        $lines = [sprintf('%s[%s] %s', $prefix, $this->getState()->getName(), $this->name)];
        foreach ($this->results as $result) {
            if ($result instanceof CheckResultSet) {
                if ($result->isEmpty()) {
                    continue;
                }
                $lines[] = $result->getOutput($prefix . '   ');
            } else {
                $lines[] = sprintf(
                    '%s%s[%s] %s',
                    $prefix,
                    self::NUMERATION_PREFIX,
                    $result->getState()->getName(),
                    $result->getOutput()
                );
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
