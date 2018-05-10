<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\ConfigurationError;

class SafeCacheDir
{
    /**
     * @return string
     * @throws ConfigurationError
     */
    public static function getDirectory()
    {
        $user = static::getCurrentUsername();
        $dirname = sprintf(
            '%s/%s-%s',
            sys_get_temp_dir(),
            'iwebVsphere',
            $user
        );

        if (file_exists($dirname)) {
            if (static::uidToName(fileowner($dirname)) !== $user) {
                throw new ConfigurationError(
                    '%s exists, but does not belong to %s',
                    $dirname,
                    $user
                );
            }
        } else {
            if (! mkdir($dirname, 0700)) {
                throw new ConfigurationError(
                    'Could not create %s',
                    $dirname
                );
            }
        }

        return $dirname;
    }

    /**
     * @return mixed
     * @throws ConfigurationError
     */
    protected static function getCurrentUsername()
    {
        if (function_exists('posix_geteuid')) {
            return static::uidToName(posix_geteuid());
        } else {
            throw new ConfigurationError(
                'POSIX methods not available, is php-posix installed and enabled?'
            );
        }
    }

    protected static function uidToName($uid)
    {
        $info = posix_getpwuid($uid);
        return $info['name'];
    }
}
