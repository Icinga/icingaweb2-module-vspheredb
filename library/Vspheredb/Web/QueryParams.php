<?php

namespace Icinga\Module\Vspheredb\Web;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class QueryParams
{
    protected array $params;

    protected function __construct(array $params)
    {
        $this->params = $params;
    }

    public static function fromRequest(ServerRequestInterface $request): QueryParams
    {
        return new static($request->getQueryParams());
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->params[$key];
        }

        return $default;
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
        }

        throw new InvalidArgumentException("Parameter '$key' is required");
    }
}
