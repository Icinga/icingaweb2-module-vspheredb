<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Web\Form;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\Html;
use ipl\I18n\Translation;

class VCenterForm extends Form
{
    use Translation;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
        $this->populate($vCenter->getProperties());
    }

    public function assemble()
    {
        $this->add(Html::tag('h3', $this->translate('Rename this vCenter')));
        $this->addElement('text', 'name', [
            'label'       => $this->translate('Name'),
            'description' => $this->translate(
                'You might want to change the display name of your vCenter.'
                . ' This defaults to the first related Server host name.'
            ),
        ]);
        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Rename')
        ]);
    }

    public function onSuccess()
    {
        $this->vCenter->setProperties($this->getValues())->store();
    }
}
