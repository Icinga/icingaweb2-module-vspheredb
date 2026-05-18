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
        $safeString = preg_replace_callback($pattern, fn($match) => Html::tag(
            'span',
            ['class' => ['check-result', 'state-' . strtolower($match[1])]],
            $match[1]
        ) . ' ', (new Text($output))->render());
        return new HtmlString($safeString);
    }
}
