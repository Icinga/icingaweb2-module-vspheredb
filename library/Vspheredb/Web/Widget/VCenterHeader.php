<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class VCenterHeader extends HtmlDocument
{
    /** @var VCenter */
    protected VCenter $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    protected function assemble(): void
    {
        $vCenter = $this->vCenter;
        $title = Html::tag('h1', [
            $vCenter->get('name'),
            ' ',
            Html::tag('small', '(' . $vCenter->getFullName() . ')'),
        ]);
        $this->add([
            $title,
        ]);
    }
}
