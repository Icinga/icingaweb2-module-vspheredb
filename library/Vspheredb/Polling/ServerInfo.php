<?php

namespace Icinga\Module\Vspheredb\Polling;

use gipfl\Json\JsonSerialization;
use gipfl\Json\JsonString;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use InvalidArgumentException;
use function array_key_exists;

class ServerInfo implements JsonSerialization
{
    /** @var array */
    protected $properties;

    /**
     * ServerInfo constructor.
     * @param array $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public static function fromSerialization($object)
    {
        // Validation will be implemented once this is remote
        return new static((array) $object);
    }

    /**
     * @param VCenterServer $server
     * @return static
     */
    public static function fromServer(VCenterServer $server)
    {
        return new static($server->getProperties());
    }

    public function isEnabled()
    {
        return $this->get('enabled') === 'y';
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->properties)) {
            if ($this->properties[$key] === null) {
                return $default;
            } else {
                return $this->properties[$key];
            }
        }

        throw new InvalidArgumentException("Trying to access invalid property: '$key'");
    }

    public function jsonSerialize()
    {
        ksort($this->properties);
        return (object) $this->properties;
    }

    public function getUrl()
    {
        return sprintf(
            '%s://%s',
            $this->get('scheme'),
            $this->get('host') // Hint: eventually contains the port
        );
    }

    public function equals(ServerInfo $info)
    {
        return JsonString::encode($info) === JsonString::encode($this);
    }

    public function getIdentifier()
    {
        return sprintf(
            '%s://%s@%s',
            $this->get('scheme'),
            rawurlencode($this->get('username')),
            $this->get('host')
        );
    }
}
