<?php

namespace Icinga\Module\Vspheredb\Web;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class QueryParams
{
    protected $params;

    protected function __construct($params)
    {
        $this->params = $params;
    }

    public static function fromRequest(ServerRequestInterface $request): QueryParams
    {
        return new static($request->getQueryParams());
    }

    public function has($key): bool
    {
        return \array_key_exists($key, $this->params);
    }

    /**
     * @param string $key
     * @param $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->params[$key];
        } else {
            return $default;
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getRequired(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->params[$key];
        } else {
            throw new InvalidArgumentException("Parameter '$key' is required");
        }
    }
}
