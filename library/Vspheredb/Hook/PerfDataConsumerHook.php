<?php

namespace Icinga\Module\Vspheredb\Hook;

use gipfl\InfluxDb\DataPoint;
use gipfl\Web\Form;
use Icinga\Application\Hook;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\LoopInterface;

/**
 * Please do not implement this Hook, it is still subject to change
 *
 * @internal
 */
abstract class PerfDataConsumerHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var array */
    protected array $settings = [];

    /** @var array */
    protected array $queue = [];

    /**
     * @param LoopInterface $loop
     * @param array|object  $settings
     *
     * @return $this
     */
    public static function initialize(LoopInterface $loop, array|object $settings = []): static
    {
        return (new static())->setLoop($loop)->setSettings($settings);
    }

    /**
     * @param LoopInterface $loop
     *
     * @return $this
     */
    public function setLoop(LoopInterface $loop): static
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * @param array|object $settings
     *
     * @return $this
     */
    public function setSettings(array|object $settings): static
    {
        $this->settings = (array) $settings;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getSetting(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        }

        return $default;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return preg_replace('/Hook$/', '', static::getClassBaseName(get_called_class()));
    }

    /**
     * @param RemoteClient $client
     *
     * @return Form
     */
    abstract public function getConfigurationForm(RemoteClient $client): Form;

    /**
     * @param RemoteClient $client
     *
     * @return Form
     */
    public function getSubscriptionForm(RemoteClient $client)
    {
        return null;
    }

    /**
     * @param PerfdataConsumer $consumer
     * @param LoopInterface    $loop
     *
     * @return static
     */
    public static function createConsumerInstance(PerfdataConsumer $consumer, LoopInterface $loop): static
    {
        $class = static::getClass($consumer->get('implementation'));
        /** @var PerfDataConsumerHook $instance */

        return $class::initialize($loop, $consumer->settings());
    }

    /**
     * Hint: Currently unused
     *
     * @param DataPoint[] $points
     *
     * @return void
     */
    public function pushDataPoints(array $points): void
    {
        if (empty($this->queue)) {
            $this->queue[] = $points;
        } elseif (count($this->queue[0]) + count($points) < 1000) {
            $this->queue[0] = array_merge($this->queue[0], $points);
        } else {
            array_unshift($this->queue, $points);
        }
    }

    /**
     * Hint: Currently unused
     *
     * Override this method, otherwise all data will be dropped
     *
     * @return bool
     */
    protected function processQueue(): bool
    {
        array_pop($this->queue);

        return true;
    }

    /**
     * @return array<string, string>
     */
    public static function enum(): array
    {
        $enum = [];
        /** @var static $instance */
        foreach (Hook::all('vspheredb/PerfDataConsumer') as $class => $instance) {
            $module = static::getModuleFromClassName($class);
            $idx = $instance::getName();
            if ($module === 'vspheredb') {
                $enum[$idx] = $idx;
            } else {
                $enum[$idx] = "$idx ($module)";
            }
        }

        return $enum;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public static function getClass(string $name): string|null
    {
        // TODO: module/Name for foreign ones?
        /** @var static $instance */
        foreach (Hook::all('vspheredb/PerfDataConsumer') as $class => $instance) {
            if ($instance::getName() === $name) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param string $class
     *
     * @return string|null
     */
    protected static function getClassBaseName(string $class): ?string
    {
        $parts = explode('\\', $class);

        return array_pop($parts);
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected static function getModuleFromClassName(string $class): string
    {
        $parts = explode('\\', ltrim($class, '\\'));
        if (count($parts) >= 3) {
            if ($parts[0] === 'Icinga' && $parts[1] === 'Module') {
                return lcfirst($parts[2]);
            }
        }

        throw new InvalidArgumentException("'$class' is not a valid Icinga Web 2 class name");
    }
}
