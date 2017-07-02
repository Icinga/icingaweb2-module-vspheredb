<?php

namespace Icinga\Module\Vsphere\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Vsphere\Api;

class CommandBase extends Command
{
    private $api;

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
}
