<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use gipfl\Translation\StaticTranslator;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState as State;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;
use Icinga\Util\Format;

class MemoryUsageHelper
{
    public const MEGA_BYTE = 1024 * 1024;

    public static function prepareState(
        Settings $settings,
        int $free,
        int $capacity,
        ?string $instanceName = null
    ): SingleCheckResult {
        $state = new State();
        if ($capacity === 0) {
            $state->raiseState(CheckPluginState::UNKNOWN);
            return new SingleCheckResult($state, sprintf(
                '%s free, but got ZERO capacity',
                Format::bytes($free, Format::STANDARD_IEC)
            ));
        }

        $percentFree = $free / $capacity * 100;
        $output = sprintf(
            '%s (%.2F%%) out of %s used, %s (%.2F%%) free',
            Format::bytes($capacity - $free, Format::STANDARD_IEC),
            100 - $percentFree,
            Format::bytes($capacity, Format::STANDARD_IEC),
            Format::bytes($free, Format::STANDARD_IEC),
            $percentFree
        );
        if ($instanceName) {
            $output = "$instanceName has $output";
        }

        $percentState = new State();
        $min = $settings->get('warning_if_less_than_percent_free');
        if ($min && ($percentFree < (float) $min)) {
            $percentState->raiseState(State::WARNING);
        }
        $min = $settings->get('critical_if_less_than_percent_free');
        if ($min && ($percentFree < (float) $min)) {
            $percentState->raiseState(State::CRITICAL);
        }

        $mbState = new State();
        $mbFree = $free / self::MEGA_BYTE;
        $min = $settings->get('warning_if_less_than_mbytes_free');
        if ($min && ($mbFree < (float) $min)) {
            $mbState->raiseState(State::WARNING);
        }
        $min = $settings->get('critical_if_less_than_mbytes_free');
        if ($min && ($mbFree < (float) $min)) {
            $mbState->raiseState(State::CRITICAL);
        }

        if ($mbState->isProblem() && $percentState->isProblem()) {
            if ($settings->get('threshold_precedence') === 'worst') {
                $state->raiseState(State::getWorst($percentState, $mbState));
            } else {
                $state->raiseState(State::getBest($percentState, $mbState));
            }
        } else {
            $state->raiseState($percentState);
            $state->raiseState($mbState);
        }

        return new SingleCheckResult($state, $output);
    }

    public static function getParameters(): array
    {
        $t = StaticTranslator::get();
        return [
            'threshold_precedence' => ['select', [
                'label' => $t->translate('Threshold/State Precedence'),
                'options' => [
                    null => $t->translate('- please choose -'),
                    'best_wins'  => $t->translate('Better state wins'),
                    'worst_wins' => $t->translate('Worse state wins'),
                ],
            ]],
            'warning_if_less_than_percent_free' => ['number', [
                'label' => $t->translate('Raise Warning with less than X percent free'),
                'placeholder' => '5',
            ]],
            'critical_if_less_than_percent_free' => ['number', [
                'label' => $t->translate('Raise Critical with less than X percent free'),
                'placeholder' => '2',
            ]],
            'warning_if_less_than_mbytes_free' => ['number', [
                'label' => $t->translate('Raise Warning with less than X MBytes free'),
                'placeholder' => '500',
            ]],
            'critical_if_less_than_mbytes_free' => ['number', [
                'label' => $t->translate('Raise Critical with less than X MBytes free'),
                'placeholder' => '100',
            ]],
        ];
    }
}
