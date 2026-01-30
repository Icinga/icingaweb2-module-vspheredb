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
    protected string $path;

    /** @var ?LoopInterface */
    protected ?LoopInterface $loop = null;

    /** @var ?UnixServer */
    protected ?UnixServer $server = null;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->eventuallyRemoveSocketFile();
    }

    /**
     * @param LoopInterface $loop
     *
     * @return void
     */
    public function run(LoopInterface $loop): void
    {
        $this->loop = $loop;
        $this->listen();
    }

    /**
     * @return void
     */
    protected function listen(): void
    {
        $old = umask(0000);
        $server = new UnixServer('unix://' . $this->path, $this->loop);
        umask($old);
        Util::forwardEvents($server, $this, ['connection', 'error']);
        $this->server = $server;
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        if ($this->server) {
            $this->server->close();
            $this->server = null;
        }

        $this->eventuallyRemoveSocketFile();
    }

    /**
     * @return void
     */
    protected function eventuallyRemoveSocketFile(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
