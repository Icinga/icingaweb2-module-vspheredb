<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\BaseHtmlElement;

class Html5UiLink extends BaseHtmlElement
{
    use TranslationHelper;

    /** @var BaseDbObject */
    protected $object;

    public $tag = 'a';

    public function __construct(VCenter $vCenter, BaseDbObject $object, $label)
    {
        /** @var HostSystem|VirtualMachine $object */
        $managedObject = $object->object();
        $server = $vCenter->getFirstServer();
        $this->setContent($label);

        if ($object instanceof VirtualMachine) {
            $url = \sprintf(
                'https://%s/ui/#/host/vms/%s',
                $server->get('host'),
                \rawurlencode($managedObject->get('moref'))
            );
        } else {
            throw new \InvalidArgumentException('No support');
        }
        $this->setAttribute('href', $url);
        $this->setAttribute('class', 'icon-home');
        $this->setAttribute('title', $this->translate('Open the VMware HTML 5 UI'));
        $this->setAttribute('target', '_blank'); // To keep the session

        // Needs testing. Here are some other examples:

        // https://vcenter.example.com/ui/#?extensionId=vsphere.core.vm.monitor&'
        //  . 'objectId=urn:vmomi:VirtualMachine:vm-12345:'
        //  . 'EA70A975-CD34-12AB-12BC-AB18AF45CEB7'
        //  . '&navigator=vsphere.core.viTree.hostsAndClustersView

        // $objectId = \sprintf(
        //     'urn:vmomi:ObjectClass:%s:%s:',
        //     'VirtualMachine', //  'ManagedObjectReference',
        //     $server->
        //     $managedObject->get('moref')
        // );
        // $url = \sprintf(
        //     'https://%s/ui/#?extensionId=%s&objectId=%s&navigator=%s',
        //     $server->get('host'), // contains port
        //     'vsphere.core.vm.summary', // or vsphere.core.vm.monitor?
        //     \rawurldecode($objectId),
        //     'vsphere.core.viTree.hostsAndClustersView'
        // );

    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
    }
}
