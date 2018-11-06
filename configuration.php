<?php

/** @var \Icinga\Application\Modules\Module $this */

$section = $this->menuSection(N_('Virtualization (VMware)'))
    ->setIcon('cloud')
    ->setUrl('vspheredb/vcenter')
    ->setPriority(70);
$section->add(N_('VCenters'))
    ->setUrl('vspheredb/resources/vcenters')
    ->setPriority(10);
$section->add(N_('Hosts'))
    ->setUrl('vspheredb/hosts')
    ->setPriority(20);
$section->add(N_('Datastores'))
    ->setUrl('vspheredb/datastores')
    ->setPriority(30);
$section->add(N_('Virtual Machines'))
    ->setUrl('vspheredb/vms')
    ->setPriority(40);
// $section->add(N_('Anomalies'))
//     ->setUrl('vspheredb/anomalies')
//     ->setPriority(45);
$section->add(N_('Event History'))
    ->setUrl('vspheredb/events')
    ->setPriority(49);
$section->add(N_('Alarm History'))
    ->setUrl('vspheredb/alarms')
    ->setPriority(49);
// $section->add(N_('Performance Counter'))
//     ->setUrl('vspheredb/configuration/counters')
//     ->setPriority(60);
// $section->add(N_('Top VMs'))
//     ->setUrl('vspheredb/top/vms')
//     ->setPriority(70);
