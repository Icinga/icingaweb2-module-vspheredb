<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;

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

        return MemoryUsageHelper::prepareState($settings, $disk->free_space, $disk->capacity, $disk->disk_path);
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
                'placeholder' => 'e.g. C:\\, /var/*, C:\\|D:\\|E:\\',
            ]],
            'disk_path_ignore' => ['text', [
                'label' => $this->translate('Ignore specific disks'),
                'placeholder' => 'e.g. C:\\, */volume-subpaths/*|/var/lib/kubelet/*',
            ]],
        ] + MemoryUsageHelper::getParameters();
    }
}
