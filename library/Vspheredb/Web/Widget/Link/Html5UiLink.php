<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use Ramsey\Uuid\Uuid;
use function rawurlencode;
use function sprintf;

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
        $server = $vCenter->getFirstServer(false);
        $this->setContent($label);

        if ($object instanceof VirtualMachine) {
            $ur = 'https://%s/ui/#?extensionId=%s&objectId=%s&navigator=%s';

            // Choose main detail view:
            //  $extension = 'vsphere.core.vm.monitor'; // Shows 'Monitor' Tab
            // $extension = 'vsphere.core.inventory.serverObjectViewsExtension';
            $extension = 'vsphere.core.vm.summary';

            // Choose left-hand tree view:
            // $navigator = 'vsphere.core.viTree.hostsAndClustersView';
            $navigator = 'vsphere.core.viTree.vmsAndTemplatesView';
            $objectId = sprintf(
                'urn:vmomi:%s:%s:%s',
                'VirtualMachine',
                $managedObject->get('moref'),
                Uuid::fromBytes($vCenter->getBinaryUuid())->toString()
            );
            $url = sprintf(
                $ur,
                $server->get('host'),
                rawurlencode($extension),
                rawurlencode($objectId),
                rawurlencode($navigator)
            );
        } elseif ($object instanceof HostSystem) {
            $url = sprintf(
                'https://%s/ui/#/host/%s',
                $server->get('host'),
                rawurlencode($managedObject->get('moref'))
            );
        } else {
            throw new InvalidArgumentException('No support');
        }
        $this->setAttribute('href', $url);
        $this->setAttribute('class', 'icon-home');
        $this->setAttribute('title', $this->translate('Open the VMware HTML 5 UI'));
        $this->setAttribute('target', '_blank'); // To keep the session
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
    }
}
