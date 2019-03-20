<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

use Clue\React\Buzz\Browser;
use React\EventLoop\LoopInterface;

class AsyncInfluxDbWriter
{
    /** @var LoopInterface */
    protected $loop;

    /** @var string */
    protected $writeUrl;

    /** @var Browser */
    protected $browser;

    /**
     * AsyncInfluxDbWriter constructor.
     * @param $writeUrl string InfluxDB write url, including /write?db=dbname
     * @param LoopInterface $loop
     */
    public function __construct($writeUrl, LoopInterface $loop)
    {
        $this->writeUrl = $writeUrl;
        $this->browser = new Browser($loop);
    }

    /**
     * @param DataPoint[] $dataPoints
     */
    public function send(array $dataPoints)
    {
        $this->browser->post($this->writeUrl, [
            // no headers for now
        ], implode($dataPoints));
    }
}
