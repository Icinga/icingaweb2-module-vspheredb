<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Form;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Table\VMotionHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\VMotionHeatmap;

class VmotionsController extends Controller
{
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

    protected function handleTabs()
    {
        $tabs = $this->tabs()->add('index', [
            'label' => $this->translate('VMotion History'),
            'url' => 'vspheredb/vmotions'
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
