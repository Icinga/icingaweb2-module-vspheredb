<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;

class SnapshotsRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'Snapshots';
    }

    public function getLabel(): string
    {
        return $this->translate('Snapshot Policy');
    }

    public function getInternalDefaults(): array
    {
        return [
            'warning_if_more_than'  => 1,
            'critical_if_more_than' => 5,
            'warning_if_older_than' => 7, // Days
            'critical_if_older_than' => 30,
        ];
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $this->assertSupportedObject($object);
        $db = $object->getConnection()->getDbAdapter();
        $info = $db->fetchRow($db->select()->from('vm_snapshot', [
            'cnt' => 'COUNT(*)',
            'ts_oldest' => 'FLOOR(MIN(ts_create) / 1000)'
        ])->where('vm_snapshot.vm_uuid = ?', $object->getConnection()->quoteBinary($object->get('uuid'))));
        $state = new CheckPluginState();
        $count = (int) $info->cnt;

        if ($count === 0) {
            $output = 'There are no snapshots';
        } else {
            $max = $settings->get('warning_if_more_than');
            if ($max && $count > $max) {
                $state->raiseState(CheckPluginState::WARNING);
            }
            $max = $settings->get('critical_if_more_than');
            if ($max && $count > $max) {
                $state->raiseState(CheckPluginState::CRITICAL);
            }
            $min = $settings->get('warning_if_older_than');
            if ($min && $info->ts_oldest < (time() - $min * 86400)) {
                $state->raiseState(CheckPluginState::WARNING);
            }
            $min = $settings->get('critical_if_older_than');
            if ($min && $info->ts_oldest < (time() - $min * 86400)) {
                $state->raiseState(CheckPluginState::CRITICAL);
            }
            $output = sprintf('%d snapshot(s), oldest one from %s', $count, date('Y-m-d H:i', $info->ts_oldest));
        }
        return [
            new SingleCheckResult($state, $output)
        ];
    }

    public function getParameters(): array
    {
        return [
            'warning_if_more_than' => ['number', [
                'label' => $this->translate('Raise Warning if more than X snapshots'),
                'placeholder' => 'unset',
            ]],
            'critical_if_more_than' => ['number', [
                'label' => $this->translate('Raise Critical if more than X snapshots'),
                'placeholder' => 'unset',
            ]],
            'warning_if_older_than' => ['number', [
                'label' => $this->translate('Raise Warning for snapshots older than X days'),
                'placeholder' => 'unset',
            ]],
            'critical_if_older_than' => ['number', [
                'label' => $this->translate('Raise Critical for snapshots older than X days'),
                'placeholder' => 'unset',
            ]],
        ];
    }
}
