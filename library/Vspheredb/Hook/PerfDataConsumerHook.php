<?php

namespace Icinga\Module\Vspheredb\Hook;

use gipfl\InfluxDb\DataPoint;
use gipfl\Web\Form;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use Icinga\Web\Hook;
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
    protected $loop;

    protected $settings = [];

    protected $queue;

    public static function initialize(LoopInterface $loop, $settings = [])
    {
        return (new static())->setLoop($loop)->setSettings($settings);
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    public function setSettings($settings)
    {
        $this->settings = (array) $settings;

        return $this;
    }

    public function getSetting($name, $default = null)
    {
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        } else {
            return $default;
        }
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return preg_replace('/Hook$/', '', static::getClassBaseName(get_called_class()));
    }

    /**
     * @return Form
     */
    abstract public function getConfigurationForm(RemoteClient $client);

    public function getSubscriptionForm(RemoteClient $client)
    {
        return null;
    }

    public static function createConsumerInstance(PerfdataConsumer $consumer, LoopInterface $loop)
    {
        $class = static::getClass($consumer->get('implementation'));
        /** @var PerfDataConsumerHook $instance */
        return $class::initialize($loop, $consumer->settings());
    }

    /**
     * Hint: Currently unused
     *
     * @var DataPoint[] $points
     */
    public function pushDataPoints($points)
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
    protected function processQueue()
    {
        array_pop($this->queue);
        return true;
    }

    public static function enum()
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
     * @param $name
     * @return string|null|static
     */
    public static function getClass($name)
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

    protected static function getClassBaseName($class)
    {
        $parts = \explode('\\', $class);
        return array_pop($parts);
    }

    protected static function getModuleFromClassName($class)
    {
        $parts = \explode('\\', ltrim($class, '\\'));
        if (count($parts) >= 3) {
            if ($parts[0] === 'Icinga' && $parts[1] === 'Module') {
                return \lcfirst($parts[2]);
            }
        }

        throw new InvalidArgumentException("'$class' is not a valid Icinga Web 2 class name");
    }
}
