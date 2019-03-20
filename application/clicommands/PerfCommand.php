<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\MappedClass\PerfEntityMetricCSV;
use Icinga\Module\Vspheredb\PerformanceData\InfluxDb\AsyncInfluxDbWriter;
use Icinga\Module\Vspheredb\PerformanceData\PerfMetricMapper;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\PerformanceSet;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\VmDisks;
use Icinga\Module\Vspheredb\PerformanceData\PerformanceSet\VmNetwork;
use Icinga\Module\Vspheredb\Rpc\Logger;
use React\EventLoop\Factory;

class PerfCommand extends CommandBase
{
    public function hostAction()
    {
        $sql = 'SELECT o.object_name, vdu.disk_path, vdu.capacity,'
          . ' vdu.free_space from object o join vm_disk_usage vdu'
          . ' on vdu.vm_uuid = o.uuid';
        $db = Db::newConfiguredInstance()->getDbAdapter();
        foreach ($db->fetchAll($sql) as $row) {
            $ciName = str_replace(' ', '_', $row->object_name);
            $path = str_replace('/', '_', $row->disk_path);
            $path = str_replace(' ', '_', $path);
            $ci = $ciName . ':' . $path;
            $lineFormat = $ci
                . ' free_space=' . $row->free_space
                . ' capacity=' . $row->capacity
                . ' '
                . time()
                . "\n";
            echo $lineFormat;
        }
    }

    public function testAction()
    {
        $destination = $this->params->getRequired('destination');
        $set = $this->params->getRequired('set');
        $loop = Factory::create();
        $influx = new AsyncInfluxDbWriter($destination, $loop);

        $sets = [
            'VmNetwork' => VmNetwork::class,
            'VmDisks'   => VmDisks::class,
        ];
        $prefixes = [
            'VmNetwork' => 'iface',
            'VmDisks'   => 'disk',
        ];
        $class = $sets[$set];
        $prefix = $prefixes[$set];

        /** @var PerformanceSet $performanceSet */
        $performanceSet = new $class($this->getVCenter());
        $counters = $performanceSet->getCounters();
        $mapper = new PerfMetricMapper($counters, $prefix);
        /** @var PerfEntityMetricCSV $metric */
        foreach ($performanceSet->fetch() as $metric) {
            $influx->send($mapper->makeInfluxDataPoints($metric));
            Logger::debug(sprintf('Sent %s metrics', count($metric)));
        }
        $loop->run();
    }
}
