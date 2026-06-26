<?php

namespace Icinga\Module\Vspheredb\Daemon;

use React\Promise\PromiseInterface;

/**
 * ReactPHP Promise compatibility helpers
 */
class PromiseUtil
{
    /**
     * Register cleanup on ReactPHP Promise v2 and v3 promises
     *
     * @param PromiseInterface $promise  Promise to register cleanup on
     * @param callable         $callback Callback receiving no arguments
     *
     * @return PromiseInterface
     */
    public static function finally(PromiseInterface $promise, callable $callback): PromiseInterface
    {
        if (method_exists($promise, 'finally')) {
            return $promise->finally($callback);
        }

        return $promise->always($callback);
    }
}
