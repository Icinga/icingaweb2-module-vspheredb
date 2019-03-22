<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

use Clue\React\Buzz\Browser;
use React\EventLoop\LoopInterface;

class AsyncInfluxDbWriter
{
    /** @var LoopInterface */
    protected $loop;

    /** @var string */
    protected $baseUrl;

    /** @var Browser */
    protected $browser;

    /**
     * AsyncInfluxDbWriter constructor.
     * @param $baseUrl string InfluxDB base URL
     * @param LoopInterface $loop
     */
    public function __construct($baseUrl, LoopInterface $loop)
    {
        $this->baseUrl = $baseUrl;
        $this->browser = new Browser($loop);
    }

    protected function url($path, $params = [])
    {
        $url = $this->baseUrl . "/$path";
        if (! empty($params)) {
            $url .= '?' . \http_build_query($params);
        }

        return $url;
    }

    /**
     * @param string $dbName
     * @param DataPoint[] $dataPoints
     */
    public function send($dbName, array $dataPoints)
    {
        $this->browser->post($this->url('write', ['db' => $dbName]), [
            'User-Agent' => 'Icinga-vSphereDB/1.0'
        ], implode($dataPoints));
    }
}
