<?php

namespace Icinga\Module\Vspheredb\Polling;

use InvalidArgumentException;

use function in_array;
use function is_int;
use function preg_match;

/**
 * Prepares an options array for a given ServerInfo object
 */
class CurlOptions
{
    /** @var array */
    public const PROXY_TYPES = [
        'HTTP'   => CURLPROXY_HTTP,
        'SOCKS5' => CURLPROXY_SOCKS5,
    ];

    public static function forServerInfo(ServerInfo $server)
    {
        $host = $server->get('host');
        if (preg_match('/^(.+?):(\d{1,5})$/', $host, $match)) {
            $host = $match[1];
            $port = (int) $match[2];
        } else {
            $port = null;
        }
        $options = [
            CURLOPT_HTTPHEADER => [
                // Host header disabled for now, see #496
                // "Host: $host",
                'Expect:',
                'User-Agent: Icinga-vSphereDB/1.6',
            ]
        ];

        // Unused, we're authenticating via SOAP
        // if (null !== ($username = $server->get('username'))) {
        //     $options[CURLOPT_USERPWD] = sprintf('%s:%s', $username, $server->get('password'));
        // }

        if ($proxyType = $server->get('proxy_type')) {
            // TODO: Depending on the PHP and Curl version, we might want to support HTTPS proxies
            $options[CURLOPT_PROXY] = 'http://' . $server->get('proxy_address');
            $options[CURLOPT_PROXYTYPE] = static::wantCurlProxyType($proxyType);

            if ($proxyUser = $server->get('proxy_user')) {
                $options[CURLOPT_PROXYUSERPWD] = sprintf(
                    '%s:%s',
                    $proxyUser,
                    $server->get('proxy_pass')
                );
            }
        }

        if ($port !== null) {
            $options[CURLOPT_PORT] = $port;
        }

        if ($server->get('scheme') === 'https') {
            if ($server->get('ssl_verify_peer') === 'n') {
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
            $options[CURLOPT_SSL_VERIFYHOST] = $server->get('ssl_verify_host') === 'n' ? 0 : 2;
        }

        return $options;
    }

    protected static function wantCurlProxyType($type)
    {
        if (is_int($type)) {
            if (in_array($type, self::PROXY_TYPES, true)) {
                return $type;
            }
        } else {
            $types = self::PROXY_TYPES;
            if (isset($types[$type])) {
                return $types[$type];
            }
        }

        throw new InvalidArgumentException("Invalid proxy type: $type");
    }
}
