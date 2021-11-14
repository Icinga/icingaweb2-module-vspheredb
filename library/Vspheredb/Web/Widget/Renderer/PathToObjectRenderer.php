<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Renderer;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use InvalidArgumentException;
use ipl\Html\Html;

class PathToObjectRenderer
{
    protected $classLinkMap = [
        VirtualMachine::class => 'vspheredb/vms',
        HostSystem::class     => 'vspheredb/hosts',
        Datastore::class      => 'vspheredb/datastores',
    ];

    public static function render(BaseDbObject $object)
    {
        $instance = new static();

        return $instance($object);
    }

    public function __invoke(BaseDbObject $object)
    {
        $uuid = $object->get('uuid');
        /** @var \Icinga\Module\Vspheredb\Db $connection */
        $connection = $object->getConnection();
        $lookup =  new PathLookup($connection->getDbAdapter());
        $class = \get_class($object);
        if (isset($this->classLinkMap[$class])) {
            $baseUrl = $this->classLinkMap[$class];
        } else {
            throw new InvalidArgumentException(
                "PathToObjectRenderer doesn't support $class"
            );
        }
        $path = Html::tag('span', ['class' => 'dc-path']);
        $parts = [];
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            if (! empty($parts)) {
                $parts[] = ' > ';
            }
            $parts[] = Link::create(
                $name,
                $baseUrl,
                // TODO: nice UUID
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            );
        }
        $path->add($parts);

        return $path;
    }
}
