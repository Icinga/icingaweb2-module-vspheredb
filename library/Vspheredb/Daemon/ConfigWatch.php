<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterTrait;
use Icinga\Application\Config;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * ConfigWatch
 *
 * Checks every 3 seconds for changed DB resource configuration. Emits a
 * 'dbConfig' event in case this happens.
 */
class ConfigWatch
{
    use EventEmitterTrait;

    const ON_CONFIG = 'dbConfig';

    /** @var string */
    protected $configFile;

    /** @var string */
    protected $resourceConfigFile;

    /** @var string|null */
    protected $dbResourceName;

    /** @var array|null */
    protected $resourceConfig;

    protected $interval = 3;

    /** @var TimerInterface */
    protected $timer;

    /** @var LoopInterface */
    protected $loop;

    public function __construct($dbResourceName = null)
    {
        $this->configFile = Config::module('vspheredb')->getConfigFile();
        if ($dbResourceName === null) {
            $this->resourceConfigFile = Config::app('resources')->getConfigFile();
        } else {
            $this->dbResourceName = $dbResourceName;
        }
    }

    /**
     * @param LoopInterface $loop
     */
    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $check = function () {
            $this->checkForFreshConfig();
        };
        $this->timer = $loop->addPeriodicTimer($this->interval, $check);
        $loop->futureTick($check);
    }

    public function stop()
    {
        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }

    protected function checkForFreshConfig()
    {
        if ($this->configHasBeenChanged()) {
            $this->emit(self::ON_CONFIG, [$this->resourceConfig]);
        }
    }

    protected function getResourceName()
    {
        if ($this->dbResourceName) {
            return $this->dbResourceName;
        } else {
            return $this->loadDbResourceName();
        }
    }

    protected function loadDbResourceName()
    {
        $parsed = @parse_ini_file($this->configFile, true);
        if (isset($parsed['db']['resource'])) {
            return $parsed['db']['resource'];
        } else {
            return null;
        }
    }

    protected function loadDbConfigFromDisk($name)
    {
        if ($name === null) {
            return null;
        }

        $parsed = @parse_ini_file($this->resourceConfigFile, true);
        if (isset($parsed[$name])) {
            $section = $parsed[$name];
            ksort($section);

            return $section;
        } else {
            return null;
        }
    }

    protected function configHasBeenChanged()
    {
        $resource = $this->loadDbConfigFromDisk($this->loadDbResourceName());
        if ($resource !== $this->resourceConfig) {
            $this->resourceConfig = $resource;

            return true;
        } else {
            return false;
        }
    }
}
