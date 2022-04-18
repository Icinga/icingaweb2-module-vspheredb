<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState as State;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;
use Icinga\Util\Format;

class DiskUsageRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'DiskUsage';
    }

    public static function isMultiInstanceRule(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return $this->translate('Disk Usage');
    }

    public function getInternalDefaults(): array
    {
        return [
            'threshold_precedence' => 'best_wins'
        ];
    }

    protected function checkDisk($disk, Settings $settings): ?SingleCheckResult
    {
        if ($filter = $settings->get('disk_path_filter')) {
            if (!$this->filterMatchesPath($filter, $disk->disk_path)) {
                return null;
            }
        }
        if ($filter = $settings->get('disk_path_ignore')) {
            if ($this->filterMatchesPath($filter, $disk->disk_path)) {
                return null;
            }
        }
        $percentFree = $disk->free_space / $disk->capacity * 100;
        $state = new CheckPluginState();
        $output = sprintf(
            '%s out of %s (%.2F%%) free on %s',
            Format::bytes($disk->free_space, Format::STANDARD_IEC),
            Format::bytes($disk->capacity, Format::STANDARD_IEC),
            $percentFree,
            $disk->disk_path
        );

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
        $mbFree = $disk->free_space / (1024 * 1024);
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

    protected function filterMatchesPath(string $filterString, string $path): bool
    {
        $parts = explode('|', $filterString);
        foreach ($parts as $part) {
            if (self::stringMatches($part, $path)) {
                return true;
            }
        }

        return false;
    }

    protected static function stringMatches(string $filterString, string $string): bool
    {
        if (strpos($filterString, '*') === false) {
            return $string === $filterString;
        }

        $parts = array();
        foreach (preg_split('~\*~', $filterString) as $part) {
            $parts[] = preg_quote($part, '/');
        }
        $pattern = '/^' . implode('.*', $parts) . '$/';

        return (bool) preg_match($pattern, $string);
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $this->assertSupportedObject($object);
        $db = $object->getConnection()->getDbAdapter();
        $disks = $db->fetchAll($db->select()->from('vm_disk_usage', [
            'disk_path',
            'capacity',
            'free_space',
        ])->where('vm_disk_usage.vm_uuid = ?', $object->getConnection()->quoteBinary($object->get('uuid'))));

        $instanceSettings = [];
        foreach ($settings->listMainKeys() as $key) {
            $instanceSettings[$key] = $settings->withRemovedKey($key);
        }

        $results = [];
        foreach ($disks as $disk) {
            foreach ($instanceSettings as $instance) {
                if ($instance->isDisabled()) {
                    continue;
                }
                if ($result = $this->checkDisk($disk, $instance)) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    public function getParameters(): array
    {
        return [
            'disk_path_filter' => ['text', [
                'label' => $this->translate('Apply to specific disks only'),
                'placeholder' => 'e.g. C:, /var/*, C:|D:|E:',
            ]],
            'disk_path_ignore' => ['text', [
                'label' => $this->translate('Ignore specific disks'),
                'placeholder' => 'e.g. C:, */volume-subpaths/*|/var/lib/kubelet/*',
            ]],
            'threshold_precedence' => ['select', [
                'label' => $this->translate('Threshold Precedence'),
                'options' => [
                    null => $this->translate('- please choose -'),
                    'best_wins' => $this->translate('Better threshold wins'),
                    'worst_wins' => $this->translate('Worse threshold wins'),
                ],
            ]],
            'warning_if_less_than_percent_free' => ['number', [
                'label' => $this->translate('Raise Warning with less then X percent free'),
                'placeholder' => '5',
            ]],
            'critical_if_less_than_percent_free' => ['number', [
                'label' => $this->translate('Raise Critical with less then X percent free'),
                'placeholder' => '2',
            ]],
            'warning_if_less_than_mbytes_free' => ['number', [
                'label' => $this->translate('Raise Warning with less then X MBytes free'),
                'placeholder' => '500',
            ]],
            'critical_if_less_than_mbytes_free' => ['number', [
                'label' => $this->translate('Raise Critical with less then X MBytes free'),
                'placeholder' => '100',
            ]],
        ];
    }
}
