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
        return match ($trigger) {
            self::RAISE_WARNING, self::RAISE_CRITICAL, self::RAISE_UNKNOWN => new CheckPluginState($trigger),
            default                                                        => new CheckPluginState()
        };
    }
}
