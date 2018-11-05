<?php

namespace Icinga\Module\Vspheredb\Rpc;

use Icinga\Application\Logger as IcingaLogger;
use Icinga\Exception\ConfigurationError;

class Logger extends IcingaLogger
{
    public static function replaceRunningInstance(JsonRpcLogWriter $writer, $level = self::DEBUG)
    {
        try {
            static::$instance
                ->setLevel($level)
                ->writer = $writer;
        } catch (ConfigurationError $e) {
            static::$instance->error($e->getMessage());
        }
    }
}
