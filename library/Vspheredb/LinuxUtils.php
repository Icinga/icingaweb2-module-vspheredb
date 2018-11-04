<?php

namespace Icinga\Module\Vspheredb;

class LinuxUtils
{
    protected static $pageSize;

    public static function getMemoryUsageForPid($pid)
    {
        $pid = (int) $pid;
        $content = @file_get_contents("/proc/$pid/statm");
        if ($content === false) {
            return false;
        }

        $pageSize = static::getPageSize();
        $parts = explode(' ', $content);

        return (object) [
            'size'   => $pageSize * (int) $parts[0],
            'rss'    => $pageSize * (int) $parts[1],
            'shared' => $pageSize * (int) $parts[3],
        ];
    }

    /**
     * @return int
     */
    public static function getPageSize()
    {
        if (self::$pageSize === null) {
            self::$pageSize = (int) trim(`getconf PAGESIZE`);
        }

        return self::$pageSize;
    }
}
