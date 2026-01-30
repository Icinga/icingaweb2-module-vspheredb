<?php

namespace Icinga\Module\Vspheredb;

/**
 * @internal This might change
 */
class Configuration
{
    public const DEFAULT_SOCKET = '/run/icinga-vspheredb/vspheredb.sock';

    private static ?string $controlSocket = null;

    public static function getSocketPath(): string
    {
        if (self::$controlSocket === null) {
            if ($path = getenv('VSPHEREDB_SOCKET')) {
                static::setControlSocket($path);
            } else {
                static::setControlSocket(self::DEFAULT_SOCKET);
            }
        }

        return self::$controlSocket;
    }

    /**
     * Allows to override the control socket
     *
     * Used for testing reasons only. Set null to re-enable the default logic
     *
     * @param string $path
     */
    public static function setControlSocket(string $path): void
    {
        self::$controlSocket = $path;
    }
}
