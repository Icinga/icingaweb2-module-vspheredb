<?php

namespace Icinga\Module\Vspheredb;

class CliUtil
{
    public static function setTitle($title)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        }
    }
}
