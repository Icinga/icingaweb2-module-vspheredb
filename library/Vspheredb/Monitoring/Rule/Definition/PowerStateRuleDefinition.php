<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\MonitoringStateTrigger;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;
use InvalidArgumentException;

class PowerStateRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::HOST_SYSTEM,
        ObjectType::VIRTUAL_MACHINE
    ];

    public static function getIdentifier(): string
    {
        return 'PowerState';
    }

    public function getLabel(): string
    {
        return $this->translate('Power State');
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        if ($object instanceof VirtualMachine) {
            $what = 'Virtual Machine';
            if ($object->get('template') === 'y') {
                return [new SingleCheckResult(CheckPluginState::OK, 'This is a VM template')];
            }
        } elseif ($object instanceof HostSystem) {
            $what = 'Host System';
        } else {
            $what = 'Object';
        }

        $powerState = $object->get('runtime_power_state');
        $state = $powerState === 'poweredOn'
            ? CheckPluginState::OK
            : MonitoringStateTrigger::nullableFrom($settings->get("trigger_on_$powerState"))->monitoringState();
        $message = $this->getStatusMessageForPowerState($powerState, $what);

        $results = [new SingleCheckResult($state, $message)];

        if ($powerState === 'poweredOn') {
            $uptimeState = CheckPluginState::OK;
            if ($object instanceof HostSystem) {
                $stats = HostQuickStats::loadFor($object);
            } else {
                assert($object instanceof VirtualMachine);
                $stats = VmQuickStats::loadFor($object);
            }
            $uptime = $stats->get('uptime');
            $output = sprintf('System booted %s ago', DateFormatter::formatDuration($uptime));

            $problemInfos = [];
            $info = null;

            foreach (
                [
                    'warning_for_uptime_less_than_seconds'  => CheckPluginState::WARNING->value,
                    'critical_for_uptime_less_than_seconds' => CheckPluginState::CRITICAL->value
                ] as $setting => $errorState
            ) {
                $min = $settings->get($setting);
                if ($min) {
                    $uptimeState = $this->checkMin($uptimeState, $uptime, $min, $errorState, $info);
                }
            }

            if ($info) {
                $problemInfos[] = $info;
                $info = null;
            }
            foreach (
                [
                    'warning_for_uptime_greater_than_days'  => CheckPluginState::WARNING->value,
                    'critical_for_uptime_greater_than_days' => CheckPluginState::CRITICAL->value
                ] as $setting => $errorState
            ) {
                $min = $settings->get($setting);
                if ($min) {
                    $uptimeState = $this->checkMax($uptimeState, $uptime, $min * 86400, $errorState, $info);
                }
            }

            if ($info) {
                $problemInfos[] = $info;
            }
            if (! empty($problemInfos)) {
                $output .= ' (' . implode(', ', $problemInfos) . ')';
            }
            $results[] = new SingleCheckResult($uptimeState, $output);
        }

        return $results;
    }

    protected function checkMax(
        CheckPluginState $uptimeState,
        int $value,
        int $threshold,
        int $errorState,
        ?string &$info
    ): CheckPluginState {
        if ($value >= $threshold) {
            $info = sprintf('>= %s ago', DateFormatter::formatDuration($threshold));

            return $uptimeState->raise(CheckPluginState::from($errorState));
        }

        return $uptimeState;
    }

    protected function checkMin(
        CheckPluginState $uptimeState,
        int $value,
        int $threshold,
        int $errorState,
        ?string &$info
    ): CheckPluginState {
        if ($value < $threshold) {
            $info = sprintf('less than %s ago', DateFormatter::formatDuration($threshold));

            return $uptimeState->raise(CheckPluginState::from($errorState));
        }

        return $uptimeState;
    }

    protected function getStatusMessageForPowerState(string $state, string $what): string
    {
        return match ($state) {
            'poweredOff' => "$what has been powered off",
            'suspended'  => "$what has been suspended",
            'unknown'    => "$what power state is unknown, might be disconnected",
            'poweredOn'  => "$what is powered on",
            default      => throw new InvalidArgumentException("'$state' is not a known power state")
        };
    }

    public function getInternalDefaults(): array
    {
        return [
            'trigger_on_poweredOff'        => MonitoringStateTrigger::RAISE_CRITICAL->value,
            'trigger_on_suspended'         => MonitoringStateTrigger::RAISE_CRITICAL->value,
            'trigger_on_unknown'           => MonitoringStateTrigger::RAISE_UNKNOWN->value,
            'warning_for_uptime_less_than' => 900
        ];
    }

    public function getParameters(): array
    {
        return [
            'trigger_on_poweredOff' => ['state_trigger', [
                'label' => $this->translate('When powered off')
            ]],
            'trigger_on_suspended' => ['state_trigger', [
                'label' => $this->translate('When suspended')
            ]],
            'trigger_on_unknown' => ['state_trigger', [
                'label'       => $this->translate('When unknown'),
                'description' => $this->translate('Might be disconnected')
            ]],
            'warning_for_uptime_less_than' => ['number', [
                'label'       => $this->translate('Raise WARNING for uptime less than'),
                'description' => $this->translate('Please provide the uptime in seconds')
            ]],
            'critical_for_uptime_less_than' => ['number', [
                'label'       => $this->translate('Raise CRITICAL for uptime less than'),
                'description' => $this->translate('Please provide the uptime in seconds')
            ]],
            'warning_for_uptime_greater_than_days' => ['number', [
                'label'       => $this->translate('Raise WARNING for uptime greater than'),
                'description' => $this->translate('Please provide the uptime in days')
            ]],
            'critical_for_uptime_greater_than_days' => ['number', [
                'label'       => $this->translate('Raise CRITICAL for uptime greater than'),
                'description' => $this->translate('Please provide the uptime in days')
            ]]
        ];
    }
}
