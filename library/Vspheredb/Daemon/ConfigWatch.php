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

    public const ON_CONFIG = 'dbConfig';

    /** @var string */
    protected string $configFile;

    /** @var ?string */
    protected ?string $resourceConfigFile = null;

    /** @var ?string */
    protected ?string $dbResourceName = null;

    /** @var ?array */
    protected ?array $resourceConfig = null;

    protected int $interval = 3;

    /** @var ?TimerInterface */
    protected ?TimerInterface $timer = null;

    /** @var ?LoopInterface */
    protected ?LoopInterface $loop = null;

    /**
     * @param ?string $dbResourceName
     */
    public function __construct(?string $dbResourceName = null)
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
     *
     * @return void
     */
    public function run(LoopInterface $loop): void
    {
        $this->loop = $loop;
        $check = function () {
            $this->checkForFreshConfig();
        };
        $this->timer = $loop->addPeriodicTimer($this->interval, $check);
        $loop->futureTick($check);
    }

    /**
     * @return void
     */
    public function stop(): void
    {
        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }

    /**
     * @return void
     */
    protected function checkForFreshConfig(): void
    {
        if ($this->configHasBeenChanged()) {
            $this->emit(self::ON_CONFIG, [$this->resourceConfig]);
        }
    }

    /**
     * @return ?string
     */
    protected function getResourceName(): ?string
    {
        return $this->dbResourceName ?: $this->loadDbResourceName();
    }

    /**
     * @return ?string
     */
    protected function loadDbResourceName(): ?string
    {
        $parsed = @parse_ini_file($this->configFile, true);

        return $parsed['db']['resource'] ?? null;
    }

    /**
     * @param ?string $name
     *
     * @return ?array
     */
    protected function loadDbConfigFromDisk(?string $name): ?array
    {
        if ($name === null) {
            return null;
        }

        $parsed = @parse_ini_file($this->resourceConfigFile, true);
        if (isset($parsed[$name])) {
            $section = $parsed[$name];
            ksort($section);

            return $section;
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function configHasBeenChanged(): bool
    {
        $resource = $this->loadDbConfigFromDisk($this->loadDbResourceName());
        if ($resource !== $this->resourceConfig) {
            $this->resourceConfig = $resource;

            return true;
        }

        return false;
    }
}
