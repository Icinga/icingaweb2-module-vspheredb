<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Hint\ConnectionStateDetails;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\Link\VCenterLink;
use Icinga\Module\Vspheredb\Web\Widget\Renderer\PathToObjectRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class VmLocationInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VirtualMachine $vm, VCenter $vCenter)
    {
        $this->prepend(new SubTitle($this->translate('Location'), 'home'));
        $this->vm = $vm;
        $this->vCenter = $vCenter;
    }

    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function assemble()
    {
        $vm = $this->vm;
        /** @var \Icinga\Module\Vspheredb\Db $connection */
        $connection = $vm->getConnection();
        $lookup =  new PathLookup($connection);

        $this->addNameValuePairs([
            $this->translate('Host') => [
                $lookup->linkToObject($vm->get('runtime_host_uuid')),
                Html::tag('br'),
                ConnectionStateDetails::getFor($vm->get('connection_state'))
            ],
            $this->translate('Resource Pool') => $lookup->linkToObject($vm->get('resource_pool_uuid')),
            $this->translate('Path') => PathToObjectRenderer::render($vm),
            $this->translate('vCenter') => new VCenterLink($this->vCenter),
        ]);
    }
}
