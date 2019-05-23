<?php

namespace Icinga\Module\Vspheredb;

use RuntimeException;

class SafeCacheDir
{
    protected static $currentUser;

    /**
     * @return string
     */
    public static function getDirectory()
    {
        $directory = sprintf(
            '%s/%s-%s',
            sys_get_temp_dir(),
            'iwebVsphere',
            static::getCurrentUsername()
        );

        static::claimDirectory($directory);

        return $directory;
    }

    /**
     * @param $directory
     * @return string
     */
    public static function getSubDirectory($directory)
    {
        $subDir = static::getDirectory() . "/$directory";
        static::claimDirectory($subDir);

        return $subDir;
    }

    /**
     * @param $directory
     */
    protected static function claimDirectory($directory)
    {
        if (file_exists($directory)) {
            if (static::uidToName(fileowner($directory)) !== static::getCurrentUsername()) {
                throw new RuntimeException(sprintf(
                    '%s exists, but does not belong to %s',
                    $directory,
                    static::getCurrentUsername()
                ));
            }
        } else {
            if (! @mkdir($directory, 0700)) {
                throw new RuntimeException(sprintf(
                    'Could not create %s',
                    $directory
                ));
            }
        }
    }

    /**
     * @return mixed
     */
    protected static function getCurrentUsername()
    {
        if (static::$currentUser === null) {
            if (function_exists('posix_geteuid')) {
                static::$currentUser = static::uidToName(posix_geteuid());
            } else {
                throw new RuntimeException(
                    'POSIX methods not available, is php-posix installed and enabled?'
                );
            }
        }

        return static::$currentUser;
    }

    protected static function uidToName($uid)
    {
        $info = posix_getpwuid($uid);
        return $info['name'];
    }
}
