<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use Icinga\Module\Vspheredb\Daemon\ConnectionState;
use Icinga\Module\Vspheredb\Monitoring\Health\ServerConnectionInfo;
use Icinga\Module\Vspheredb\Polling\ApiConnection;

class VCenterConnectionStatusIcon
{
    public static function create(ServerConnectionInfo $info): Icon
    {
        $title = ['title' => ConnectionState::describe($info)];

        return match ($info->getState()) {
            'unknown'                      => Icon::create('help', ['class' => 'unknown'] + $title),
            'disabled'                     => Icon::create('cancel', $title),
            ApiConnection::STATE_CONNECTED => Icon::create('ok', ['class' => 'green'] + $title),
            ApiConnection::STATE_LOGIN,
            ApiConnection::STATE_INIT      => Icon::create('spinner', ['class' => 'yellow'] + $title),
            ApiConnection::STATE_FAILING   => Icon::create('warning-empty', ['class' => 'red'] + $title),
            ApiConnection::STATE_STOPPING  => Icon::create('cancel', ['class' => 'yellow'] + $title),
            default                        => Icon::create('warning-empty', ['class' => 'warning'] + $title)
        };
    }

    public static function noServer(): Icon
    {
        return Icon::create('warning-empty', ['class' => 'yellow', 'title' => ConnectionState::describeNoServer()]);
    }
}
