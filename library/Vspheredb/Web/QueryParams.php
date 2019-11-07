<?php

namespace Icinga\Module\Vspheredb\Web;

use Psr\Http\Message\ServerRequestInterface;

class QueryParams
{
    protected $params;

    protected function __construct($params)
    {
        $this->params = $params;
    }

    public static function fromRequest(ServerRequestInterface $request)
    {
        return new static($request->getQueryParams());
    }

    public function has($key)
    {
        return \array_key_exists($key, $this->params);
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->params[$key];
        } else {
            return $default;
        }
    }

    public function getRequired($key)
    {
        if ($this->has($key)) {
            return $this->params[$key];
        } else {
            throw new \InvalidArgumentException("Parameter '$key' is required");
        }
    }
}
