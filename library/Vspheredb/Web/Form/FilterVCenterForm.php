<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use ipl\Html\Form;
use Ramsey\Uuid\Uuid;

class FilterVCenterForm extends Form
{
    use TranslationHelper;

    protected $db;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->setMethod('GET');
    }

    public function getHexUuid()
    {
        return $this->getElement('vcenter')->getValue();
    }

    public function onSuccess()
    {
    }

    protected function assemble()
    {
        $enum = $this->enumVCenters();
        $this->addElement('select', 'vcenter', [
            'options' => [
                null => $this->translate('- please choose -'),
            ] + $enum,
            'class'   => 'autosubmit',
            'value'   => key($enum),
        ]);
    }

    protected function enumVCenters()
    {
        $pairs = $this->db->fetchPairs(
            $this->db->select()->from(
                ['vc' => 'vcenter'],
                [
                    'uuid' => 'LOWER(HEX(vc.instance_uuid))',
                    'name' => "vc.name || ' (' || REPLACE(vc.api_name, 'VMware ', '') || ')'",
                ]
            )->order('vc.name')
        );
        $enum = [];
        foreach ($pairs as $uuid => $label) {
            $enum[Uuid::fromString($uuid)->toString()] = $label;
        }

        return $enum;
    }
}
