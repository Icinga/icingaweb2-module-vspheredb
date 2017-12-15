<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseElement;
use dipl\Html\Html;

class CompactInOutSparkline extends BaseElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'sparks'];

    public function __construct($in, $out)
    {
        if ($in !== null) {
            $this->add(
                $this->makeSparkLine($in)
                    ->addAttributes(['class' => 'in'])
            );
        }

        if ($out !== null && $out !== '0,0,0,0,0') {
            $this->add(
                $this->makeSparkLine($this->negateString($out))
                    ->addAttributes(['class' => 'out'])
            );
        }
    }

    protected function negateString($valueString)
    {
        return '-' . implode(',-', explode(',', $valueString));
    }

    protected function makeSparkLine($values)
    {
        if ($values === null) {
            return null;
        }
        return Html::tag('span', [
            'class'            => 'sparkline',
            'sparkType'        => 'bar',
            'sparkBarColor'    => '#44bb77',
            'sparkNegBarColor' => '#0095BF',
            'sparkBarWidth'    => 7,
            'values'           => $values
        ]);
    }
}
