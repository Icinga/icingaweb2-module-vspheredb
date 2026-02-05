<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

enum MonitoringStateTrigger: string
{
    case IGNORE = 'ignore';
    case RAISE_WARNING  = 'warning';
    case RAISE_CRITICAL = 'critical';
    case RAISE_UNKNOWN  = 'unknown';

    /**
     * Allow to create a trigger out of null. Null leads to the IGNORE case.
     *
     * @param string|null $from
     *
     * @return self
     */
    public static function nullableFrom(?string $from): self
    {
        if ($from === null) {
            return self::IGNORE;
        }

        return self::from($from);
    }

    /**
     * Get the monitoring state for the trigger
     *
     * @return CheckPluginState
     */
    public function monitoringState(): CheckPluginState
    {
        return match ($this) {
            self::RAISE_WARNING  => CheckPluginState::fromTrigger(self::RAISE_WARNING),
            self::RAISE_CRITICAL => CheckPluginState::fromTrigger(self::RAISE_CRITICAL),
            self::RAISE_UNKNOWN  => CheckPluginState::fromTrigger(self::RAISE_UNKNOWN),
            default              => CheckPluginState::OK
        };
    }
}
