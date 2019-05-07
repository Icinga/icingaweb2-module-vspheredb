<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Util;

class VCenterInfoTable extends NameValueTable
{
    use TranslationHelper;

    protected $vcenter;

    public function __construct(VCenter $vcenter)
    {
        $this->vcenter = $vcenter;
    }

    protected function assemble()
    {
        $c = $this->vcenter;

        $this->addNameValuePairs([
            $this->translate('Name') => $c->get('name'),
            $this->translate('Info') => sprintf(
                '%s %s build-%s',
                $c->get('api_type'),
                $c->get('version'),
                $c->get('build')
            ),
            $this->translate('UUID') => Util::uuidToHex($c->get('instance_uuid')),
            // $this->translate('Version') => $c->get('version'),
            $this->translate('OS Type') => $c->get('os_type'),
            $this->translate('API Type') => $c->get('api_type'),
            $this->translate('API Version') => $c->get('api_version'),
            // $this->translate('Build') => $c->get('build'),
            $this->translate('Vendor') => $c->get('vendor'),
            $this->translate('Product Line') => $c->get('product_line'),
            $this->translate('license Product Name') => $c->get('license_product_name'),
            $this->translate('license Product Version') => $c->get('license_product_version'),
            $this->translate('Locale Build') => $c->get('locale_build'),
            $this->translate('Locale Version') => $c->get('locale_version'),
        ]);
    }
}
