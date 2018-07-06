<?php

namespace Icinga\Module\Vspheredb\Svg;

use dipl\Html\BaseHtmlElement;
use dipl\Html\DeferredText;
use dipl\Html\Html;

class VmotionsSvg extends BaseHtmlElement
{
    protected $tag = 'svg';

    protected $defaultAttributes = [
        'xmlns'  => 'http://www.w3.org/2000/svg',
        'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
        // 'style' => 'width: 100%'
    ];

    private $width = 1400;

    private $motionHeight = 20;

    private $zoom = 1;

    private $motions;

    private $begin;

    private $end;

    private $totalDuration;

    /**
     * VmotionsSvg constructor.
     * @param $motions
     * @param $begin
     * @param $end
     */
    public function __construct($motions, $begin, $end)
    {
        $this->motions = $motions;
        $this->begin = $begin;
        $this->end = $end;
        $this->totalDuration = $end - $begin;
    }

    public function zoom($zoom = 1)
    {
        $this->zoom = $zoom;

        return $this;
    }

    protected function renderMotion($offsetY, $row)
    {
        $begin = $row->ts_init ?: $this->begin;
        $end = $row->ts_end ?: $this->end;
        $duration = $end - $begin;

        $classes = ['vmotion'];
        if ($row->succeeded === true) {
            $classes[] = 'ok';
        } elseif ($row->succeeded === false) {
            $classes[] = 'failed';
        } else {
            $classes[] = 'pending';
        }

        $offsetX = (($begin - $this->begin) / $this->totalDuration) * $this->width;
        $width = ($duration / $this->totalDuration) * $this->width;

        return SvgUtils::rectangle($width, $this->motionHeight, [
            'id'    => 'vmotion_' . md5(base64_encode(serialize($row))),
            'x'     => SvgUtils::float($offsetX),
            'y'     => SvgUtils::float($offsetY),
            'class' => $classes,
            'title' => sprintf('%s (%s -> ? - %s:%s)', $row->vm_name, $row->src_hostname, $begin, $end),
        ]);
    }

    protected function assemble()
    {
        $height = SvgUtils::float($this->motionHeight * count($this->motions) * $this->zoom);
        $width  = SvgUtils::float($this->width * $this->zoom);
        $this->addAttributes([
            'viewBox' => "0 0 $width $height",
            'width'   => $width,
            'height'  => $height,
        ]);
        $this->add(Html::tag('desc', null, 'VMotion Debug'));
        $this->addGradientDefinition();
        $y = 0;
        foreach ($this->motions as $key => $motions) {
            foreach ($motions as $motion) {
                $this->add($this->renderMotion($y * $this->motionHeight, $motion));
            }
            $y++;
        }
        $this->add(SvgUtils::rectangle(0, 39, [
            'x'  => SvgUtils::float(0),
            'y'  => SvgUtils::float(0),
            'class' => 'vmarker',
        ]));
        $this->add(SvgUtils::rectangle(0, 39, [
            'x'  => SvgUtils::float(0),
            'y'  => SvgUtils::float(0),
            'class' => 'vpos',
        ]));
    }

    protected function addGradientDefinition()
    {
        $this->add(Html::tag('defs', null, [
            Html::tag('linearGradient', [
                'id' => 'fillOk',
                'x1' => '0%',
                'y1' => '0%',
                'x2' => '0%',
                'y2' => '100%',
            ], [
                Html::tag('stop', [
                    'offset' => '0%',
                    'style' => 'stop-color:rgb(68,187,119);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '100%',
                    'style' => 'stop-color:rgb(136,213,169);stop-opacity:1',
                ]),
            ]),
            Html::tag('linearGradient', [
                'id' => 'fillProblem',
                'x1' => '0%',
                'y1' => '0%',
                'x2' => '0%',
                'y2' => '100%',
            ], [
                Html::tag('stop', [
                    'offset' => '0%',
                    'style' => 'stop-color:rgb(255,85,102);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '100%',
                    'style' => 'stop-color:rgb(255,140,150);stop-opacity:1',
                ]),
            ]),
            Html::tag('linearGradient', [
                'id' => 'fillPending',
                'x1' => '0%',
                'y1' => '0%',
                'x2' => '0%',
                'y2' => '100%',
            ], [
                Html::tag('stop', [
                    'offset' => '0%',
                    // 'style' => 'stop-color:rgb(255,85,102);stop-opacity:0.25',
                    'style' => 'stop-color:rgb(212,204,222);stop-opacity:0.5',
                ]),
                Html::tag('stop', [
                    'offset' => '100%',
                    'style' => 'stop-color:rgb(224,224,224);stop-opacity:0.5',
                ]),
/*
                Html::tag('stop', [
                    'offset' => '0%',
                    'style' => 'stop-color:rgb(68,187,119);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '70%',
                    'style' => 'stop-color:rgb(136,213,169);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '70%',
                    'style' => 'stop-color:rgb(255,85,102);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '90%',
                    'style' => 'stop-color:rgb(255,140,150);stop-opacity:1',
                ]),
                Html::tag('stop', [
                    'offset' => '90%',
                    'style' => 'stop-color:rgb(68,187,119);stop-opacity:0.5',
                ]),
                Html::tag('stop', [
                    'offset' => '100%',
                    'style' => 'stop-color:rgb(136,213,169);stop-opacity:0.5',
                ]),
*/
            ])
        ]));
    }

    /*
     */

    /*
    protected function tryInlineCss(BaseElement $svg)
    {
        $svg->add(Html::tag('defs', null, Html::tag('style', ['type' => 'text/css'], FormattedString::create(
            '<![CDATA[
        rect {
          fill: #dfac20;
          stroke: #3983ab;
          stroke-width: 2;
        }
        circle {
        fill: red;
        }
      ]]>'
        ))));
    }
    */
}
