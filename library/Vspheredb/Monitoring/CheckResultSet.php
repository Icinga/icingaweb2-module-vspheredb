<?php

namespace Icinga\Module\Vspheredb\Monitoring;

class CheckResultSet implements CheckResultInterface
{
    public const NUMERATION_PREFIX = ' \\_ ';

    /** @var string */
    protected $name;

    /** @var CheckResultInterface[] */
    protected $results = [];

    protected $prependedOutput = '';

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
        $indent = strlen($prefix . self::NUMERATION_PREFIX . '[');
        $lines = [sprintf('%s[%s] %s', $prefix, $this->getState()->getName(), $this->name)];
        if ($this->prependedOutput !== '') {
            $lines[] = $this->indent($this->prependedOutput, $indent - 4);
        }
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
                    $this->indentAllButFirstLine($result->getOutput(), $indent)
                );
            }
        }

        return implode(PHP_EOL, $lines);
    }

    public function prependOutput(string $output)
    {
        $this->prependedOutput .= $output;
    }

    protected function indentAllButFirstLine(string $string, int $spaces): string
    {
        $lines = explode(PHP_EOL, rtrim($string));
        return array_shift($lines) . $this->indent(implode(PHP_EOL, $lines), $spaces);
    }

    protected function indent(string $string, int $spaces): string
    {
        $lines = explode(PHP_EOL, rtrim($string, PHP_EOL));
        $output = '';
        $prefix = str_repeat(' ', $spaces);
        foreach ($lines as $line) {
            if ($output === '') {
                $output .= "$prefix$line";
            } else {
                $output .= "\n$prefix$line";
            }
        }

        return "$output"; // Hint: "$output\n" would be "correcter"
    }
}
