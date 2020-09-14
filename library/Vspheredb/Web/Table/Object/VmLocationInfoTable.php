<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Hint\ConnectionStateDetails;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\Link\MobLink;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

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

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
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
            $this->translate('Path') => $this->renderPathToObject(),
            $this->translate('MO Ref') => new MobLink($this->vCenter, $vm),
            $this->translate('vCenter') => Link::create(
                $this->vCenter->get('name'),
                'vspheredb/vcenter',
                ['vcenter' => Uuid::fromBytes($this->vCenter->getUuid())->toString()]
            )
        ]);
    }

    protected function renderPathToObject()
    {
        $uuid = $this->vm->get('uuid');
        /** @var \Icinga\Module\Vspheredb\Db $connection */
        $connection = $this->vm->getConnection();
        $lookup =  new PathLookup($connection);
        $path = Html::tag('span', ['class' => 'dc-path']);
        $parts = [];
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            if (! empty($parts)) {
                $parts[] = ' > ';
            }
            $parts[] = Link::create(
                $name,
                'vspheredb/vms',
                // TODO: nice UUID
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            );
        }
        $path->add($parts);

        return $path;
    }
}
