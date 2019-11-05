<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class BadUsernameSessionEvent extends SessionEvent
{
    /**
     * The IP address of the peer that initiated the connection.
     *
     * This may be the client that originated the session, or it may be an
     * intervening proxy if the binding uses a protocol that supports proxies,
     * such as HTTP.
     *
     * @var string
     */
    public $ipAddress;
}
