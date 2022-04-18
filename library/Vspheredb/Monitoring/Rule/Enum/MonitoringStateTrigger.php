<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;

class MonitoringStateTrigger
{
    public const IGNORE         = 'ignore';
    public const RAISE_WARNING  = 'warning';
    public const RAISE_CRITICAL = 'critical';
    public const RAISE_UNKNOWN  = 'unknown';

    public static function getMonitoringState(?string $trigger): CheckPluginState
    {
        switch ($trigger) {
            case self::RAISE_WARNING:
            case self::RAISE_CRITICAL:
            case self::RAISE_UNKNOWN:
                return new CheckPluginState($trigger);
        }

        return new CheckPluginState();
    }
}
