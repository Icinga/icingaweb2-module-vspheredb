<?php

namespace Icinga\Module\Vspheredb\Rpc;

use Icinga\Application\Logger as IcingaLogger;
use Icinga\Exception\ConfigurationError;

class Logger extends IcingaLogger
{
    public static function replaceRunningInstance(JsonRpcLogWriter $writer, $level = null)
    {
        try {
            $instance = static::$instance;
            if ($level !== null) {
                $instance->setLevel($level);
            }

            $instance->writer = $writer;
        } catch (ConfigurationError $e) {
            static::$instance->error($e->getMessage());
        }
    }
}
