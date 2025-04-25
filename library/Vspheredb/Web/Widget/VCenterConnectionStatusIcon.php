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
        $state = $info->getState();
        $title = ConnectionState::describe($info);
        switch ($state) {
            case 'unknown':
                return Icon::create('help', ['class' => 'unknown', 'title' => $title]);
            case 'disabled':
                return Icon::create('cancel', ['title' => $title]);
            case ApiConnection::STATE_CONNECTED:
                return Icon::create('ok', ['class' => 'green', 'title' => $title]);
            case ApiConnection::STATE_LOGIN:
            case ApiConnection::STATE_INIT:
                return Icon::create('spinner', ['class' => 'yellow', 'title' => $title]);
            case ApiConnection::STATE_FAILING:
                return Icon::create('warning-empty', ['class' => 'red', 'title' => $title]);
            case ApiConnection::STATE_STOPPING:
                return Icon::create('cancel', ['class' => 'yellow', 'title' => $title]);
        }

        // Fail, error?
        return Icon::create('warning-empty', ['class' => 'warning', 'title' => $title]);
    }

    public static function noServer(): Icon
    {
        return Icon::create('warning-empty', ['class' => 'yellow', 'title' => ConnectionState::describeNoServer()]);
    }
}
