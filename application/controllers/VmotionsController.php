<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Form;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Svg\VmotionsSvg;
use Icinga\Module\Vspheredb\Web\Table\VMotionHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\VMotionHeatmap;

class VmotionsController extends Controller
{
    /**
     * @throws \Icinga\Exception\Http\HttpNotFoundException
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function init()
    {
        $this->handleTabs();
    }

    public function indexAction()
    {
        $day = $this->params->shift('day');

        $table = new VMotionHistoryTable($this->db());
        $dayStamp = strtotime($day);
        if ($day) {
            $this->addTitle('VMotion History on %s', DateFormatter::formatDate($dayStamp));
            $table->getQuery()->where(
                'ts_event_ms >= ?',
                strtotime("$day 00:00:00") * 1000
            )->where(
                'ts_event_ms <= ?',
                (strtotime("$day 23:59:59") + 1) * 1000
            );
        } else {
            $this->addTitle('VMotion History');
        }
        $table->renderTo($this);
    }

    public function debugAction()
    {
        $begin = strtotime('2018-06-21 09:08:00') * 1000;
        $begin = strtotime('2018-06-21 09:00:00') * 1000;
        $end = strtotime('2018-06-21 11:30:00') * 1000;

        //$begin = strtotime('2018-04-21 09:00:00') * 1000;
        //$end = strtotime('2018-06-21 14:30:00') * 1000;

        //$end = strtotime('2018-06-21 10:00:00') * 1000;
        // $end = strtotime('2018-06-21 00:00:00') * 1000;

        // $begin = strtotime('2018-05-11 08:30:00') * 1000;
        // $end = strtotime('2018-05-11 10:30:00') * 1000;

        $db = $this->db()->getDbAdapter();
        $query = $db->select()->from([
            'vh' => 'vm_event_history'
        ], [
            'vm' => 'o.object_name',
            'src_host' => 'ho.object_name',
            'vh.ts_event_ms',
            'vh.event_type',
            'vh.vm_uuid',
            'vh.host_uuid',
            'vh.user_name',
            'vh.datastore_uuid',
            'vh.destination_host_uuid',
            'vh.destination_datastore_uuid',
            'vh.full_message',
            'vh.fault_reason',
        ])->join(
            ['o' => 'object'],
            'o.uuid = vh.vm_uuid',
            []
        )->join(
            ['ho' => 'object'],
            'ho.uuid = vh.host_uuid',
            []
        )->order('ts_event_ms ASC');

        $query->where('event_type IN (?)', [
            'VmFailedMigrateEvent',
            'MigrationEvent',
            'VmBeingMigratedEvent',
            'VmBeingHotMigratedEvent',
            'VmEmigratingEvent',
            'VmMigratedEvent',
        ]);

        $query->where('ho.object_name LIKE ?', '%dmz%rz%');
        // $query->where('ho.object_name = ?', 'dmz-esxi-rz2-06.ka.de.dm-drogeriemarkt.com');

        $query->where('vh.ts_event_ms >= ?', $begin);
        $query->where('vh.ts_event_ms <= ?', $end);

        $motions = [];

        foreach ($db->fetchAll($query) as $row) {
            $id = $row->vm_uuid;
            if (array_key_exists($id, $motions)) {
                $current = $motions[$id][count($motions[$id]) - 1];
                if ($current->ts_end) {
                    $current = $this->newmotion($row);
                    $motions[$id][] = $current;
                }
            } else {
                $current = $this->newmotion($row);
                $motions[$id] = [$current];
            }

            switch ($row->event_type) {
                case 'VmEmigratingEvent':
                    $current->ts_begin = (int) $row->ts_event_ms;
                    break;
                case 'VmBeingMigratedEvent':
                case 'VmBeingHotMigratedEvent':
                    $current->ts_init = (int) $row->ts_event_ms;
                    break;
                case 'VmMigratedEvent':
                    $current->succeeded = true;
                    $current->ts_end = (int) $row->ts_event_ms;
                    break;
                case 'VmFailedMigrateEvent':
                    $current->succeeded = false;
                    $current->ts_end = (int) $row->ts_event_ms;
                    break;
                default:
                    throw new \RuntimeException(sprintf(
                        'Unexpected Event: %s',
                        $row->event_type
                    ));
            }

            if ($current->src_host === null && $row->host_uuid !== null) {
                $current->src_host = $row->host_uuid;
            }
            if ($current->dst_host === null && $row->destination_host_uuid !== null) {
                $current->dst_host = $row->destination_host_uuid;
            }
            if ($current->src_datastore === null && $row->datastore_uuid !== null) {
                $current->src_datastore = $row->datastore_uuid;
            }
            if ($current->dst_datastore === null && $row->destination_datastore_uuid !== null) {
                $current->dst_datastore = $row->destination_datastore_uuid;
            }
        }

        $this->content()->add(new VmotionsSvg($motions, $begin, $end));
    }

    protected function newmotion($row)
    {
        return (object) [
            'vm_name' => $row->vm,
            'src_hostname' => $row->src_host,
            'ts_init'  => null,
            'ts_begin' => null,
            'ts_end'   => null,
            'src_host' => null,
            'dst_host' => null,
            'src_datastore' => null,
            'dst_datastore' => null,
            'succeeded'     => null,
        ];
    }

    public function heatmapAction()
    {
        $form = new Form();
        $form->setMethod('GET');
        $form->addElement('parent', 'select', [
            'options' => [
                null => $this->translate('- filter -')
            ] + $this->enumHostParents(),
            'class' => 'autosubmit',
        ]);
        $form->handleRequest($this->getRequest());
        $this->addTitle('VMotion Heatmap');

        $heatMap = new VMotionHeatmap($this->vCenter(), 'vspheredb/vmotions');
        if ($parent = $this->params->get('parent')) {
            $heatMap->filterParent(hex2bin($parent));
        }
        $this->content()->add($form);
        $this->content()->add($heatMap);
    }

    protected function enumHostParents()
    {
        $db = $this->db()->getDbAdapter();
        $query = $db->select()->from(
            ['p' => 'object'],
            ['p.uuid', 'p.object_name']
        )->join(
            ['c' => 'object'],
            'c.parent_uuid = p.uuid AND '
            . $db->quoteInto('c.object_type = ?', 'HostSystem')
            . ' AND '
            . $db->quoteInto('p.object_type = ?', 'ClusterComputeResource'),
            []
        )->group('p.uuid')->order('p.object_name');

        $enum = [];
        foreach ($db->fetchPairs($query) as $k => $v) {
            $enum[bin2hex($k)] = $v;
        }

        return $enum;
    }

    /**
     * @throws \Icinga\Exception\Http\HttpNotFoundException
     * @throws \Icinga\Exception\ProgrammingError
     */
    protected function handleTabs()
    {
        $tabs = $this->tabs()->add('index', [
            'label' => $this->translate('VMotion History'),
            'url' => 'vspheredb/vmotions'
        ]);

        $this->tabs()->add('debug', [
            'label' => $this->translate('Debug'),
            'url' => 'vspheredb/vmotions/debug'
        ]);

        if (! $this->params->get('day')) {
            $tabs->add('heatmap', [
                'label' => $this->translate('Heatmap'),
                'url' => 'vspheredb/vmotions/heatmap'
            ]);
        }

        $tabs->activate($this->getRequest()->getActionName());
    }
}
