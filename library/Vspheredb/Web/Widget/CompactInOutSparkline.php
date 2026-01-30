<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class CompactInOutSparkline extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'sparks'];

    public function __construct(array|string|null $in, array|string|null $out)
    {
        if ($in !== null) {
            $this->add(
                $this->makeSparkLine($in)
                    ->addAttributes(Attributes::create(['class' => 'in']))
            );
        }

        if ($out !== null && $out !== '0,0,0,0,0') {
            $this->add(
                $this->makeSparkLine($this->negateString($out))
                    ->addAttributes(Attributes::create(['class' => 'out']))
            );
        }
    }

    protected function negateString(string $valueString): string
    {
        return '-' . implode(',-', explode(',', $valueString));
    }

    protected function makeSparkLine($values): ?HtmlElement
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
