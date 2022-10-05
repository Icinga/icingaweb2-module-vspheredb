<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\Text;

class CheckPluginHelper
{
    public static function colorizeOutput(string $output): HtmlString
    {
        $pattern = '/\[(OK|WARNING|CRITICAL|UNKNOWN)]\s/';
        $safeString = (new Text($output))->render();
        $safeString = preg_replace_callback($pattern, function ($match) {
            $state = strtolower($match[1]);
            return Html::tag('span', ['class' => ['check-result', "state-$state"]], $match[1]) . ' ';
        }, $safeString);
        return new HtmlString($safeString);
    }
}
