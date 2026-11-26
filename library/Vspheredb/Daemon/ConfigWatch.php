<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Daemon;

use ErrorException;
use Evenement\EventEmitterTrait;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use TypeError;

/**
 * ConfigWatch
 *
 * Checks every 3 seconds for changed DB resource configuration. Emits a
 * 'dbConfig' event in case this happens.
 */
class ConfigWatch implements LoggerAwareInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    public const ON_CONFIG = 'dbConfig';

    /** @var string */
    protected $configFile;

    /** @var string */
    protected $resourceConfigFile;

    protected ?string $dbResourceName;

    /** @var array|null */
    protected $resourceConfig;

    protected ?string $lastReadError = null;

    protected $interval = 3;

    /** @var TimerInterface */
    protected $timer;

    /** @var LoopInterface */
    protected $loop;

    /**
     * Create a new ConfigWatch
     *
     * @param ?string $dbResourceName Fixed resource name, or null to read it from the module configuration
     */
    public function __construct(?string $dbResourceName = null)
    {
        $this->configFile = Config::resolvePath('modules/vspheredb/config.ini');
        $this->resourceConfigFile = Config::resolvePath('resources.ini');
        $this->dbResourceName = $dbResourceName;
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

    /**
     * Get the configured database resource name
     *
     * @return ?string The explicit or current configured name, or null when no name is configured
     *
     * @throws ConfigurationError If the module configuration cannot be parsed
     * @throws NotReadableError If the module configuration cannot be read
     */
    protected function getResourceName(): ?string
    {
        return $this->dbResourceName ?? Config::fromIni($this->configFile)->get('db', 'resource');
    }

    /**
     * Load the named database resource from disk
     *
     * @param ?string $name Database resource name
     *
     * @return ?array<string, mixed> Configuration ordered by option name, or null when the resource is absent
     *
     * @throws ConfigurationError If the resource file cannot be parsed
     * @throws NotReadableError If the resource file cannot be read
     */
    protected function loadDbConfigFromDisk(?string $name): ?array
    {
        if ($name === null) {
            return null;
        }

        $config = Config::fromIni($this->resourceConfigFile);
        if (! $config->hasSection($name)) {
            return null;
        }

        $section = $config->getSection($name)->toArray();
        ksort($section);

        return $section;
    }

    /**
     * Check whether the database resource changed and record the new configuration
     *
     * Read and parse failures leave the last configuration active while polling continues.
     * Repeated failures are logged once until the next successful poll.
     *
     * @return bool Whether a successful read changed the stored configuration
     */
    protected function configHasBeenChanged(): bool
    {
        $file = $this->configFile;
        try {
            $name = $this->getResourceName();
            $file = $this->resourceConfigFile;
            $resource = $this->loadDbConfigFromDisk($name);
        } catch (ConfigurationError | ErrorException | NotReadableError | TypeError $e) {
            $operation = $e instanceof ConfigurationError || $e instanceof TypeError ? 'parse' : 'read';
            $message = sprintf('[configwatch] Cannot %s INI file "%s"', $operation, $file);
            if ($message !== $this->lastReadError && $this->logger !== null) {
                $this->logger->error($message);
                $this->lastReadError = $message;
            }

            return false;
        }

        $this->lastReadError = null;
        if ($resource !== $this->resourceConfig) {
            $this->resourceConfig = $resource;

            return true;
        } else {
            return false;
        }
    }
}
