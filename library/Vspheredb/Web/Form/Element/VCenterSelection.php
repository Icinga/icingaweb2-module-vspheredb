<?php

namespace Icinga\Module\Vspheredb\Web\Form\Element;

use gipfl\Translation\TranslationHelper;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Auth\RestrictionHelper;
use Icinga\Module\Vspheredb\Db;
use ipl\Html\FormElement\SelectElement;
use Ramsey\Uuid\Uuid;

class VCenterSelection extends SelectElement
{
    use TranslationHelper;

    /** @var Db */
    protected $connection;

    /** @var Auth */
    protected $auth;

    protected $optional = false;

    public function __construct(Db $connection, Auth $auth, $required = false, $name = 'vcenter', $attributes = null)
    {
        $this->connection = $connection;
        $this->auth = $auth;
        parent::__construct($name, $attributes);
        $enum = $this->enumVCenters();
        $this->addAttributes([
            'options' => $required ? $enum : [
                null => $this->translate('All vCenters'),
            ] + $enum,
            'class' => 'autosubmit',
        ]);
    }

    protected function enumVCenters()
    {
        $db = $this->connection->getDbAdapter();
        $pairs = $db->fetchPairs(
            $db->select()->from(['vc' => 'vcenter'], [
                'uuid' => 'LOWER(HEX(vc.instance_uuid))',
                'name' => "vc.name || ' (' || REPLACE(vc.api_name, 'VMware ', '') || ')'",
            ])->order('vc.name')
        );
        $enum = [];
        $helper = new RestrictionHelper($this->auth, $this->connection);
        foreach ($pairs as $uuid => $label) {
            if ($helper->allowsVCenter($uuid)) {
                $enum[Uuid::fromString($uuid)->toString()] = $label;
            }
        }

        return $enum;
    }
}
