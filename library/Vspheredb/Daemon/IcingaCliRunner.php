<?php

namespace Icinga\Module\Vspheredb\Daemon;

use React\ChildProcess\Process;

class IcingaCliRunner
{
    /** @var string */
    protected string $binary;

    /** @var ?string */
    protected ?string $cwd = null;

    /** @var ?array */
    protected ?array $env = null;

    /**
     * @param string $binary
     */
    public function __construct(string $binary)
    {
        $this->binary = $binary;
    }

    /**
     * @param array|null $argv
     *
     * @return IcingaCliRunner
     */
    public static function forArgv(?array $argv = null): IcingaCliRunner
    {
        if ($argv === null) {
            global $argv;
        }

        // TODO: eventually strip PHP? Check this!
        return new static($argv[0]);
    }

    /**
     * @param mixed $arguments array|...string
     *
     * @return Process
     */
    public function command(...$arguments): Process
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        return new Process(
            $this->escapedCommand($arguments),
            $this->cwd,
            $this->env
        );
    }

    /**
     * @param string|null $cwd
     *
     * @return void
     */
    public function setCwd(?string $cwd): void
    {
        $this->cwd = $cwd;
    }

    /**
     * @param array|null $env
     *
     * @return void
     */
    public function setEnv(?array $env): void
    {
        $this->env = $env;
    }

    /**
     * @param array $arguments
     *
     * @return string
     */
    protected function escapedCommand(array $arguments): string
    {
        $command = ['exec', escapeshellcmd($this->binary)];

        foreach ($arguments as $argument) {
            if (ctype_alnum(preg_replace('/^\-{1,2}/', '', $argument))) {
                $command[] = $argument;
            } else {
                $command[] = escapeshellarg($argument);
            }
        }

        return implode(' ', $command);
    }
}
