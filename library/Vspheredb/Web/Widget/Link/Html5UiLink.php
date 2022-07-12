<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\BaseHtmlElement;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use function rawurlencode;
use function sprintf;

class Html5UiLink extends BaseHtmlElement
{
    use TranslationHelper;

    const QUERYSTRING_V7 = '/ui/#?extensionId=%s&objectId=%s&navigator=%s';
    const V6_QUERYSTRING = [
        HostSystem::class     => '/ui/#/host/%s',
        VirtualMachine::class => '/ui/#/host/vms/%s',
    ];
    const OBJECT_TYPES = [
        HostSystem::class     => 'HostSystem',
        VirtualMachine::class => 'VirtualMachine',
    ];

    // left-hand tree view:
    const V7_NAVIGATOR = [
        HostSystem::class     => 'vsphere.core.viTree.hostsAndClustersView',
        VirtualMachine::class => 'vsphere.core.viTree.vmsAndTemplatesView',
    ];
    const V7_EXTENSION = [
        // Choose main detail view:
        //  $extension = 'vsphere.core.vm.monitor'; // Shows 'Monitor' Tab
        // $extension = 'vsphere.core.inventory.serverObjectViewsExtension';
        HostSystem::class     => 'vsphere.core.host.summary',
        VirtualMachine::class => 'vsphere.core.vm.summary',
    ];

    /** @var BaseDbObject */
    protected $object;

    public $tag = 'a';

    public function __construct(VCenter $vCenter, BaseDbObject $object, $label)
    {
        $this->setContent($label);
        $this->setAttribute('href', self::prepareUrl($vCenter, $object));
        $this->setAttribute('class', 'icon-home');
        $this->setAttribute('title', $this->translate('Open the VMware HTML 5 UI'));
        $this->setAttribute('target', '_blank'); // To keep the session
    }

    protected static function prepareUrl(VCenter $vCenter, BaseDbObject $object)
    {
        $url = self::prepareBaseUrl($vCenter);
        if (self::isV7($vCenter)) {
            $url .= self::linkV7($object, $vCenter);
        } else {
            $url .= self::linkV6($object);
        }

        return $url;
    }

    protected static function prepareBaseUrl(VCenter $vCenter)
    {
        return 'https://' . $vCenter->getFirstServer(false)->get('host');
    }

    protected static function isV7(VCenter $vCenter)
    {
        return version_compare($vCenter->get('version'), '7.0.0', '>=');
    }

    protected static function linkV6(BaseDbObject $object)
    {
        return sprintf(self::pick(self::V6_QUERYSTRING, $object), rawurlencode($object->object()->get('moref')));
    }

    protected static function linkV7(BaseDbObject $object, VCenter $vCenter)
    {
        return sprintf(
            self::QUERYSTRING_V7,
            rawurlencode(self::pick(self::V7_EXTENSION, $object)),
            rawurlencode(self::prepareV7ObjectId($vCenter, $object)),
            rawurlencode(self::pick(self::V7_NAVIGATOR, $object))
        );
    }

    protected static function prepareV7ObjectId(VCenter $vCenter, BaseDbObject $object)
    {
        return sprintf(
            'urn:vmomi:%s:%s:%s',
            self::pick(self::OBJECT_TYPES, $object),
            self::moref($object),
            Uuid::fromBytes($vCenter->getBinaryUuid())->toString()
        );
    }

    protected static function moref(BaseDbObject $object)
    {
        return $object->object()->get('moref');
    }

    protected static function pick(array $list, BaseDbObject $object)
    {
        $class = get_class($object);
        if (isset($list[$class])) {
            return $list[$class];
        }

        throw new RuntimeException("Unable to generate HTML5 UI link for $class");
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
    }
}
