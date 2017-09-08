<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Application\Config;
use Icinga\Cli\Command;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;

class CommandBase extends Command
{
    /** @var Api */
    private $api;

    /** @var Db */
    private $db;

    protected function api()
    {
        if ($this->api === null) {
            $p = $this->params;
            $scheme = $p->get('use-insecure-http') ? 'HTTP' : 'HTTPS';

            $this->api = new Api(
                $p->getRequired('vhost'),
                $p->getRequired('username'),
                $p->getRequired('password'),
                $scheme
            );

            $curl = $this->api->curl();

            if ($proxy = $p->get('proxy')) {
                if ($proxyType = $p->get('proxy-type')) {
                    $curl->setProxy($proxy, $proxyType);
                } else {
                    $curl->setProxy($proxy);
                }

                if ($user = $p->get('proxy_user')) {
                    $curl->setProxyAuth($user, $p->get('proxy_pass'));
                }
            }

            if ($scheme === 'HTTPS') {
                if ($p->get('no-ssl-verify-peer')) {
                    $curl->disableSslPeerVerification();
                }
                if ($p->get('no-ssl-verify-host')) {
                    $curl->disableSslHostVerification();
                }
            }
        }

        return $this->api;
    }

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::fromResourceName(
                Config::module('vsphere')->get('db', 'resource')
            );
        }

        return $this->db;
    }
}
