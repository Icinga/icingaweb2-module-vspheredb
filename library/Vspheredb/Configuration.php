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
        return self::$controlSocket ??= getenv('VSPHEREDB_SOCKET') ?: self::DEFAULT_SOCKET;
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
