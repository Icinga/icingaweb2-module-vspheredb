<?php

namespace Icinga\Module\Vspheredb\PerformanceData\PerformanceSet;

class VmNetwork extends PerformanceSet
{
    protected $counters = [
        'bytesRx', // rate / average / kiloBytesPerSecond
        'bytesTx',
        'packetsRx',
        'packetsTx',
        'broadcastRx',
        'broadcastTx',
        'multicastRx',
        'multicastTx',
        'droppedRx',
        'droppedTx',
    ];

    protected $countersGroup = 'net';

    protected $objectType = 'VirtualMachine';

    public function getMetrics()
    {
        $db = $this->getDb();
        return $db->fetchPairs(
            $db->select()->from(['o' => 'object'], [
                'o.moref',
                'hardware_key' => "GROUP_CONCAT(vna.hardware_key SEPARATOR ',')",
            ])
            ->join(['vm' => 'virtual_machine'], 'o.uuid = vm.uuid', [])
            ->join(['vna' => 'vm_network_adapter'], 'vna.vm_uuid = vm.uuid', [])
            ->where('o.vcenter_uuid = ?', $this->vCenter->getUuid())
            ->group('vm.uuid')
            ->order('vm.runtime_host_uuid')
            ->order('vna.hardware_key')
        );
    }
}
