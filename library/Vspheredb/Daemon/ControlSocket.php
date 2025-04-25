<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\Socket\UnixServer;
use React\Stream\Util;

use function file_exists;
use function umask;
use function unlink;

class ControlSocket implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var string */
    protected $path;

    /** @var LoopInterface */
    protected $loop;

    /** @var UnixServer */
    protected $server;

    public function __construct($path)
    {
        $this->path = $path;
        $this->eventuallyRemoveSocketFile();
    }

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->listen();
    }

    protected function listen()
    {
        $old = umask(0000);
        $server = new UnixServer('unix://' . $this->path, $this->loop);
        umask($old);
        Util::forwardEvents($server, $this, ['connection', 'error']);
        $this->server = $server;
    }

    public function shutdown()
    {
        if ($this->server) {
            $this->server->close();
            $this->server = null;
        }

        $this->eventuallyRemoveSocketFile();
    }

    protected function eventuallyRemoveSocketFile()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
