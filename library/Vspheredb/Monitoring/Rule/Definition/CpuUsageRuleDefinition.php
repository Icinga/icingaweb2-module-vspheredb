<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;

class CpuUsageRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::HOST_SYSTEM,
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'CpuUsage';
    }

    public function getLabel(): string
    {
        return $this->translate('CPU Usage');
    }

    public function getInternalDefaults(): array
    {
        return [];
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $this->assertSupportedObject($object);

        if ($object instanceof HostSystem) {
            $quickStats = HostQuickStats::loadFor($object);
            $cpuCount = $object->get('hardware_cpu_cores');
            $mhzSingleCpu = $object->get('hardware_cpu_mhz');
        } else {
            assert($object instanceof VirtualMachine);
            $quickStats = VmQuickStats::loadFor($object);
            $cpuCount = $object->get('hardware_numcpu');
            if ($object->hasRuntimeHost()) {
                try {
                    $mhzSingleCpu = $object->getRuntimeHost()->get('hardware_cpu_mhz');
                } catch (NotFoundError $e) {
                    $mhzSingleCpu = 2000;
                }
            } else {
                $mhzSingleCpu = 2000;
            }
        }
        $state = new CheckPluginState();
        $mhzUsed = $quickStats->get('overall_cpu_usage');
        $mhzCapacity = $mhzSingleCpu * $cpuCount;
        $mhzFree = $mhzCapacity - $mhzUsed;
        if ($mhzCapacity === 0) {
            $state->raiseState(CheckPluginState::UNKNOWN);
            return [
                new SingleCheckResult($state, sprintf(
                    '%s used, but got ZERO capacity (%d CPUs, %s per CPU)',
                    Format::mhz($mhzUsed),
                    $cpuCount,
                    Format::mhz($mhzSingleCpu)
                ))
            ];
        }

        $percentFree = $mhzFree / $mhzCapacity * 100;
        $output = sprintf(
            '%s (%.2F%%) out of %s used, %s (%.2F%%) free',
            Format::mhz($mhzUsed),
            100 - $percentFree,
            Format::mhz($mhzCapacity),
            Format::mhz($mhzFree),
            $percentFree
        );

        $min = $settings->get('warning_if_less_than_percent_free');
        if ($min && ($percentFree < (float) $min)) {
            $state->raiseState(CheckPluginState::WARNING);
        }
        $min = $settings->get('critical_if_less_than_percent_free');
        if ($min && ($percentFree < (float) $min)) {
            $state->raiseState(CheckPluginState::CRITICAL);
        }

        return [
            new SingleCheckResult($state, $output)
        ];
    }

    public function getParameters(): array
    {
        return [
            'warning_if_less_than_percent_free' => ['number', [
                'label' => $this->translate('Raise Warning with less than X percent free'),
                'placeholder' => '30',
            ]],
            'critical_if_less_than_percent_free' => ['number', [
                'label' => $this->translate('Raise Critical with less than X percent free'),
                'placeholder' => '10',
            ]],
        ];
    }
}
