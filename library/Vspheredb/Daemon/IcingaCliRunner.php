<?php

namespace Icinga\Module\Vspheredb\Daemon;

use React\ChildProcess\Process;

class IcingaCliRunner
{
    /** @var string */
    protected $binary;

    /** @var string|null */
    protected $cwd;

    /** @var array|null */
    protected $env;

    public function __construct($binary)
    {
        $this->binary = $binary;
    }

    /**
     * @param array|null $argv
     * @return IcingaCliRunner
     */
    public static function forArgv(array $argv = null)
    {
        if ($argv === null) {
            global $argv;
        }

        // TODO: eventually strip PHP? Check this!
        return new static($argv[0]);
    }

    /**
     * @param mixed array|...$arguments
     * @return Process
     */
    public function command($arguments = null)
    {
        if (! is_array($arguments)) {
            $arguments = func_get_args();
        }

        return new Process(
            $this->escapedCommand($arguments),
            $this->cwd,
            $this->env
        );
    }

    /**
     * @param string|null $cwd
     */
    public function setCwd($cwd)
    {
        if ($cwd === null) {
            $this->cwd = $cwd;
        } else {
            $this->cwd = (string) $cwd;
        }
    }

    /**
     * @param array|null $env
     */
    public function setEnv($env)
    {
        if ($env === null) {
            $this->env = $env;
        } else {
            $this->env = (array) $env;
        }
    }

    /**
     * @param $arguments
     * @return string
     */
    protected function escapedCommand($arguments)
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
