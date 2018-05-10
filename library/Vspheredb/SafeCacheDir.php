<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\ConfigurationError;

class SafeCacheDir
{
    protected static $currentUser;

    /**
     * @return string
     * @throws ConfigurationError
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
     * @throws ConfigurationError
     */
    public static function getSubDirectory($directory)
    {
        echo "Getting sub dir $directory\n";
        $subDir = static::getDirectory() . "/$directory";
        static::claimDirectory($subDir);

        return $subDir;
    }

    /**
     * @param $directory
     * @throws ConfigurationError
     */
    protected static function claimDirectory($directory)
    {
        if (file_exists($directory)) {
            if (static::uidToName(fileowner($directory)) !== static::getCurrentUsername()) {
                throw new ConfigurationError(
                    '%s exists, but does not belong to %s',
                    $directory,
                    static::getCurrentUsername()
                );
            }
        } else {
            if (! mkdir($directory, 0700)) {
                throw new ConfigurationError(
                    'Could not create %s',
                    $directory
                );
            }
        }
    }

    /**
     * @return mixed
     * @throws ConfigurationError
     */
    protected static function getCurrentUsername()
    {
        if (static::$currentUser === null) {
            if (function_exists('posix_geteuid')) {
                static::$currentUser = static::uidToName(posix_geteuid());
            } else {
                throw new ConfigurationError(
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
