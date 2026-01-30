<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Date\DateFormatter;
use ipl\Html\Error as HtmlError;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use Throwable;
use Exception;

class WebUtil
{
    public static function runFailSafe(callable $callback, HtmlDocument $parent): void
    {
        try {
            $callback();
        } catch (Exception $e) {
            $parent->add(HtmlError::show($e));
        } catch (Throwable $e) {
            $parent->add(HtmlError::show($e));
        }
    }

    public static function timeAgo(float|int $time): HtmlElement
    {
        return Html::tag('span', [
            'class' => 'time-ago',
            'title' => DateFormatter::formatDateTime($time)
        ], DateFormatter::timeAgo($time));
    }
}
