<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState as State;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\MonitoringStateTrigger as Trigger;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;
use ipl\Html\Html;

use function preg_match;
use function sprintf;

class GuestUtilitiesRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'GuestUtilities';
    }

    public function getLabel(): string
    {
        return $this->translate('Guest Utilities Policy');
    }

    public function getSuggestedSettings(): array
    {
        return [
            'on_vcenter_complaint' => Trigger::RAISE_WARNING,
            'on_not_installed'     => Trigger::RAISE_WARNING,
            'on_not_running'       => Trigger::RAISE_WARNING,
            'version_2147483647'   => Trigger::IGNORE,
        ];
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $state = new State();
        $version = $object->get('guest_tools_version');
        $versionInfo = '';

        if ($version === '2147483647') {
            $versionInfo = "v$version";
            $state->raiseState(Trigger::getMonitoringState($settings->get('version_2147483647')));
        } elseif (
            $version !== null && (
            preg_match('/^([89])(\d{1})(\d{2})$/', $version, $m)
            || preg_match('/^(1\d)(\d{1})(\d{2})$/', $version, $m)
            )
        ) {
            $version = sprintf('%d.%d.%d', $m[1], $m[2], $m[3]);
            $versionInfo = "v$version";
            $required = $settings->get('warning_if_less_than');
            if ($required && version_compare($version, $required) < 0) {
                $versionInfo .= ", less than $required";
                $state->raiseState(State::WARNING);
            }
            $required = $settings->get('critical_if_less_than');
            if ($required && version_compare($version, $required) < 0) {
                $versionInfo .= ", less than $required";
                $state->raiseState(State::CRITICAL);
            }
        }

        switch ($object->get('guest_tools_status')) {
            case 'toolsNotInstalled':
                $message = 'Guest Tools are NOT installed';
                $state->raiseState(Trigger::getMonitoringState($settings->get('on_not_installed')));
                break;
            case 'toolsNotRunning':
                $message = sprintf('Guest Tools (%s) are NOT running', $versionInfo);
                $state->raiseState(Trigger::getMonitoringState($settings->get('on_not_running')));
                break;
            case 'toolsOld':
                $message = sprintf('Guest Tools (%s) are old (considered outdated by VMware)', $versionInfo);
                $state->raiseState(Trigger::getMonitoringState($settings->get('on_vcenter_complaint')));
                break;
            case 'toolsOk':
                $message = sprintf('Guest Tools (%s) are up to date and running', $versionInfo);
                break;
            case null:
            default:
                $message = 'Guest Tools status is now known';
        }

        return [
            new SingleCheckResult($state, $message)
        ];
    }

    public function getParameters(): array
    {
        return [
            'on_vcenter_complaint' => ['state_trigger', [
                'label' => $this->translate('When the vCenter says "outdated"'),
            ]],
            'on_not_installed' => ['state_trigger', [
                'label' => $this->translate('When not installed'),
            ]],
            'on_not_running' => ['state_trigger', [
                'label' => $this->translate('When installed, but not running'),
            ]],
            'version_2147483647' => ['state_trigger', [
                'label' => $this->translate('On version 2147483647'),
                'description' => Html::sprintf(
                    $this->translate('Please read %s'),
                    Link::create('KB 51988', 'https://kb.vmware.com/s/article/51988')
                ),
            ]],
            'warning_if_less_than' => ['text', [
                'label' => $this->translate('Raise Warning for versions lower than'),
                'placeholder' => '00.0.00'
            ]],
            'critical_if_less_than' => ['text', [
                'label' => $this->translate('Raise Critical for versions lower than'),
                'placeholder' => '00.0.00'
            ]],
        ];
    }
}
