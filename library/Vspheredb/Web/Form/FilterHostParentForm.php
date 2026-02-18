<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Util;
use Zend_Db_Adapter_Abstract;

class FilterHostParentForm extends Form
{
    use TranslationHelper;

    protected $method = 'GET';

    protected $useFormName = false;

    protected $useCsrf = false;

    protected Zend_Db_Adapter_Abstract $db;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
    }

    public function hasDefaultElementDecorator(): false
    {
        return false;
    }

    protected function assemble(): void
    {
        $vMotionEvents = [
            // 'MigrationEvent',
            'VmBeingMigratedEvent',
            'VmBeingHotMigratedEvent',
            'VmEmigratingEvent',
            'VmMigratedEvent',
            'VmFailedMigrateEvent'
        ];

        $otherKnownEvents = [
            'VmStartingEvent',
            'VmPoweredOnEvent',
            'VmStoppingEvent',
            'VmPoweredOffEvent',
            'VmResettingEvent',
            'VmBeingCreatedEvent',
            'VmCreatedEvent',
            'VmReconfiguredEvent',
            'VmSuspendedEvent',
            'VmBeingDeployedEvent',
            'VmBeingClonedEvent',
            'VmBeingClonedNoFolderEvent',
            'VmClonedEvent',
            'VmCloneFailedEvent'
        ];

        $this->addElement('select', 'type', [
            'options' => ['' => $this->translate('- filter by event type -')]
                + array_combine($vMotionEvents, $vMotionEvents)
                + array_combine($otherKnownEvents, $otherKnownEvents),
            'class' => 'autosubmit'
        ]);
        $parents = $this->enumHostParents();
        if (empty($parents)) {
            $element = $this->createElement('hidden', 'parent');
            $this->prepend($element);
            $this->registerElement($element);
        } else {
            $this->addElement('select', 'parent', [
                'options' => ['' => $this->translate('- filter by parent -')] + $parents,
                'class' => 'autosubmit',
            ]);
        }
    }

    public function onSuccess(): void
    {
        // Overriding ipl method, would otherwise render a "success" paragraph
    }

    public function getColors(): array
    {
        $colors = [
            'VmPoweredOffEvent' => [255, 0, 0],
            'VmResettingEvent' => [164, 0, 0],
            'VmBeingHotMigratedEvent' => [255, 164, 0],
            'VmReconfiguredEvent' => [164, 0, 128],
            'VmPoweredOnEvent' => [0, 164, 0],
            'VmCreatedEvent' => [0, 164, 0],
            'VmStartingEvent' => [119, 170, 255],
            'VmBeingCreatedEvent' => [119, 170, 255],
        ];

        $type = $this->getElement('type')->getValue() ?? '';

        return $colors[$type] ?? $colors['VmReconfiguredEvent'];
    }

    protected function enumHostParents(): array
    {
        $db = $this->db;
        $query = $db->select()->from(['p' => 'object'], ['p.uuid', 'p.object_name'])->join(
            ['c' => 'object'],
            sprintf(
                'c.parent_uuid = p.uuid AND %s AND %s',
                $db->quoteInto('c.object_type = ?', 'HostSystem'),
                $db->quoteInto('p.object_type = ?', 'ClusterComputeResource')
            ),
            []
        )->group('p.uuid')->order('p.object_name');

        $enum = [];
        foreach ($db->fetchPairs($query) as $k => $v) {
            $enum[Util::niceUuid($k)] = $v;
        }

        return $enum;
    }
}
