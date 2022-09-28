<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;

class RpcNamespaceProcess implements EventEmitterInterface
{
    use EventEmitterTrait;

    const ON_RESTART = 'restart';

    /** @var LoopInterface */
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /*
    public function infoRequest()
    {
        return $this->prepareProcessInfo($this->daemon);
    }

    protected function prepareProcessInfo(Daemon $daemon)
    {
        $details = $this->daemon->getProcessDetails()->getPropertiesToInsert();
        $details['process_info'] = \json_decode($details['process_info']);

        return (object) [
            'state'   => $this->daemon->getProcessState()->getInfo(),
            'details' => (object) $details,
        ];
    }
    */

    /**
     * @return bool
     */
    public function restartRequest()
    {
        // Grant some time to ship the response
        $this->loop->addTimer(0.1, function () {
            $this->emit(self::ON_RESTART);
        });

        return true;
    }
}
