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

    public const QUERYSTRING = '/ui/#?extensionId=%s&objectId=%s&navigator=%s';
    public const QUERYSTRING_LEGACY = [
        HostSystem::class     => '/ui/#/host/%s',
        VirtualMachine::class => '/ui/#/host/vms/%s',
    ];
    public const OBJECT_TYPES = [
        HostSystem::class     => 'HostSystem',
        VirtualMachine::class => 'VirtualMachine',
    ];

    // left-hand tree view:
    public const NAVIGATOR = [
        HostSystem::class     => 'vsphere.core.viTree.hostsAndClustersView',
        VirtualMachine::class => 'vsphere.core.viTree.vmsAndTemplatesView',
    ];
    public const EXTENSION = [
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
        if (self::isLegacy($vCenter)) {
            $url .= self::linkLegacy($object);
        } else {
            $url .= self::linkHtml5Ui($object, $vCenter);
        }

        return $url;
    }

    protected static function prepareBaseUrl(VCenter $vCenter)
    {
        return 'https://' . $vCenter->getFirstServer(false)->get('host');
    }

    protected static function isLegacy(VCenter $vCenter)
    {
        return version_compare($vCenter->get('version'), '6.7.0', '<');
    }

    protected static function linkLegacy(BaseDbObject $object)
    {
        return sprintf(self::pick(self::QUERYSTRING_LEGACY, $object), rawurlencode($object->object()->get('moref')));
    }

    protected static function linkHtml5Ui(BaseDbObject $object, VCenter $vCenter)
    {
        return sprintf(
            self::QUERYSTRING,
            rawurlencode(self::pick(self::EXTENSION, $object)),
            rawurlencode(self::prepareV7ObjectId($vCenter, $object)),
            rawurlencode(self::pick(self::NAVIGATOR, $object))
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
