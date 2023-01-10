<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Img;
use gipfl\IcingaWeb2\Link;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\Web\Widget\DelayedPerfdataRenderer;
use Icinga\Module\Vspheredb\Web\Widget\GuestToolsStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Web\Widget\Renderer\GuestToolsVersionRenderer;
use Icinga\Module\Vspheredb\Format;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class VmsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    protected $searchColumns = [
        'object_name',
        'guest_host_name',
        'guest_ip_address',
        'moref',
    ];

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vm.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $wantsHosts = false;
        $wantsStats = false;
        $wantsVCenter = false;
        foreach ($columns as $column) {
            if (substr($column, 0, 2) === 'h.') {
                $wantsHosts = true;
            }
            if (substr($column, 0, 4) === 'vqs.') {
                $wantsStats = true;
            }
            if (substr($column, 0, 3) === 'vc.') {
                $wantsVCenter = true;
            }
        }

        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['vm' => 'virtual_machine'],
            'o.uuid = vm.uuid',
            []
        );

        if ($wantsStats) {
            $query->join(
                ['vqs' => 'vm_quick_stats'],
                'vqs.uuid = vm.uuid',
                []
            );
        }

        if ($wantsVCenter) {
            $query->join(
                ['vc' => 'vcenter'],
                'vc.instance_uuid = vm.vcenter_uuid',
                []
            );
        }

        if ($wantsHosts) {
            $query->joinLeft(
                ['h' => 'host_system'],
                'vm.runtime_host_uuid = h.uuid',
                []
            );
        }
        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }
        if ($this->filterVCenter) {
            $query->where('o.vcenter_uuid = ?', $this->filterVCenter->getUuid());
        }

        return $query;
    }

    protected function initialize()
    {
        $powerStateRenderer = new PowerStateRenderer();
        $guestToolsStatusRenderer = new GuestToolsStatusRenderer();
        $guestToolsVersionRenderer = new GuestToolsVersionRenderer();
        $memoryRenderer = function ($row) {
            return new MemoryUsage(
                $row->guest_memory_usage_mb,
                $row->hardware_memorymb,
                $row->host_memory_usage_mb
            );
        };
        $memoryColumns = [
            'guest_memory_usage_mb' => 'vqs.guest_memory_usage_mb',
            'host_memory_usage_mb'  => 'vqs.host_memory_usage_mb',
            'hardware_memorymb'     => 'vm.hardware_memorymb',
        ];
        $this->addAvailableColumns([
            $this->createColumn('runtime_power_state', $this->translate('Power'), 'vm.runtime_power_state')
                ->setRenderer($powerStateRenderer),

            $this->createOverallStatusColumn(),

            $this->createObjectNameColumn(),

            $this->createColumn(
                'guest_tools_status',
                $this->translate('Guest Tools'),
                'vm.guest_tools_status'
            )->setRenderer($guestToolsStatusRenderer)->setSortExpression('vm.guest_tools_status'),

            $this->createColumn(
                'guest_tools_version',
                $this->translate('Tools Version'),
                'vm.guest_tools_version'
            )
            ->setRenderer($guestToolsVersionRenderer)
            ->setSortExpression(
                "CAST("
                . "CASE WHEN guest_tools_version = '2147483647' THEN '1' ELSE guest_tools_version END"
                . " AS SIGNED INTEGER)"
            ),

            $this->createColumn('host_name', $this->translate('Host'), 'h.host_name'),
            $this->createColumn('vcenter_name', $this->translate('vCenter / ESXi'), 'vc.name'),

            $this->createColumn('guest_ip_address', $this->translate('Guest IP'), 'vm.guest_ip_address'),

            $this->createColumn('hardware_numcpu', $this->translate('vCPUs'), 'vm.hardware_numcpu')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('cpu_usage', $this->translate('CPU Usage'), 'vqs.overall_cpu_usage')
                ->setRenderer(function ($row) {
                    return Format::mhz($row->cpu_usage);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('hardware_memorymb', $this->translate('Memory'), 'vm.hardware_memorymb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('guest_memory_usage_mb', $this->translate('Active Memory'), 'vqs.guest_memory_usage_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->guest_memory_usage_mb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('host_memory_usage_mb', $this->translate('Host Memory'), 'vqs.host_memory_usage_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->host_memory_usage_mb);
                })->setSortExpression('vqs.host_memory_usage_mb')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('ballooned_memory_mb', $this->translate('Balloon'), 'vqs.ballooned_memory_mb')
                ->setRenderer(function ($row) {
                    return Format::mBytes($row->ballooned_memory_mb);
                })->setDefaultSortDirection('DESC'),

            $this->createColumn('memory_usage', $this->translate('Memory Usage'), $memoryColumns)
                ->setRenderer($memoryRenderer)
                ->setSortExpression('(vqs.guest_memory_usage_mb / vm.hardware_memorymb)')
                ->setDefaultSortDirection('DESC'),

            $this->createColumn('uptime', $this->translate('Uptime'), [
                'uptime' => 'vqs.uptime',
            ])->setRenderer(function ($row) {
                if ($row->uptime === null) {
                    return null;
                }

                return DateFormatter::formatDuration($row->uptime);
            }),
            /*
            TODO: Not yet
            $this->createColumn('ifTraffic', $this->translate('NIC Usage'), [
                'moref' => 'o.moref',
            ])->setRenderer(function ($row) {
                return $this->renderInterface($row->moref, 4000);
            }),
            */
        ]);

        // $this->addPerfColumns();
    }

    protected function renderInterface($moref, $hardwareKey)
    {
        $width = 160;
        $height = 30;
        $rand = rand(0, 3600);
        $end = floor((time() - $rand) / 300) * 300;
        $start = $end - $rand;
        $params = [
            'file'     => sprintf('%s/iface%s.rrd', $moref, $hardwareKey),
            'height'   => $height,
            'width'    => $width,
            'rnd'      => floor(time() / 20),
            'format'   => 'png',
            'start'    => $start,
            'end'      => $end,
        ];
        $attrs = [
            'height' => $height,
            'width'  => $width,
            //'align'  => 'right',
            // 'style'  => 'border-bottom: 1px solid rgba(0, 0, 0, 0.3); border-left: 1px solid rgba(0, 0, 0, 0.3);'
        ];

        return Img::create('rrd/img', $params + [
            'template' => 'vSphereDB-vmIfTraffic',
        ], $attrs);
    }

    protected function addPerfColumns()
    {
        $perf = new DelayedPerfdataRenderer($this->db());
        $this->addAvailableColumns([
            $perf->getDiskColumn()->setDefaultSortDirection('DESC'),
            $perf->getNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentDiskColumn()->setDefaultSortDirection('DESC'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'object_name',
            'cpu_usage',
            'memory_usage',
        ];
    }

    protected function getDefaultSortColumns()
    {
        return ['object_name'];
    }

    protected function createObjectNameColumn()
    {
        return $this->createColumn('object_name', $this->translate('Name'), [
            'object_name'         => 'o.object_name',
            'overall_status'      => 'o.overall_status',
            'runtime_power_state' => 'vm.runtime_power_state',
            'template'            => 'vm.template',
            'uuid'                => 'o.uuid',
        ])->setRenderer(function ($row) {
            if (in_array('overall_status', $this->getChosenColumnNames())) {
                $result = [];
            } else {
                $statusRenderer = $this->overallStatusRenderer();
                $result = [$statusRenderer($row)];
            }
            $name = Anonymizer::anonymizeString($row->object_name);
            if ($row->template === 'y') {
                $name = [$name, Html::tag('i', ' (' . $this->translate('Template') . ')')];
            }
            if ($this->baseUrl === null) {
                $result[] = $name;
            } else {
                $result[] = Link::create($name, $this->baseUrl, ['uuid' => Uuid::fromBytes($row->uuid)->toString()]);
            }

            return $result;
        });
    }
}
