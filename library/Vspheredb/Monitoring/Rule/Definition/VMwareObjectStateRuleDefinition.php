<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\MonitoringStateTrigger;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;

class VMwareObjectStateRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::HOST_SYSTEM,
        ObjectType::VIRTUAL_MACHINE,
        ObjectType::DATASTORE,
    ];

    public static function getIdentifier(): string
    {
        return 'VMwareObjectState';
    }

    public function getLabel(): string
    {
        return $this->translate('VMware Object State');
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $color = $object->object()->get('overall_status');
        $state = MonitoringStateTrigger::getMonitoringState($settings->get("trigger_on_$color"));
        $message = $this->getStatusMessageForColor($color);

        return [
            new SingleCheckResult($state, $message)
        ];
    }

    protected function getStatusMessageForColor($color): string
    {
        $message = "Overall VMware status is '$color'";
        if ($color === 'gray') {
            $message .= ', VM might be unreachable';
        }

        return $message;
    }

    public function getInternalDefaults(): array
    {
        return [
            'trigger_on_gray'   => MonitoringStateTrigger::RAISE_CRITICAL,
            'trigger_on_yellow' => MonitoringStateTrigger::RAISE_WARNING,
            'trigger_on_red'    => MonitoringStateTrigger::RAISE_CRITICAL
        ];
    }

    public function getParameters(): array
    {
        return [
            'trigger_on_yellow' => ['state_trigger', [
                'label' => $this->translate('When VMware shows YELLOW'),
            ]],
            'trigger_on_gray' => ['state_trigger', [
                'label' => $this->translate('When VMware shows GRAY'),
                'description' => $this->translate('VM might be unreachable')
            ]],
            'trigger_on_red' => ['state_trigger', [
                'label' => $this->translate('When VMware shows RED'),
            ]],
        ];
    }
}
