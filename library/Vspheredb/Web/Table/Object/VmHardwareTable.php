<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Chart\Primitive\Path;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Util\Format;

class VmHardwareTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    protected $parents = [];

    protected $children = [];

    protected $disks = [];

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    /**
     * @return Db
     */
    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function fetchDisks()
    {
        $db = $this->getDb()->getDbAdapter();
        $query = $db->select()
            ->from('vm_disk')
            ->where('vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_key');

        foreach ($db->fetchAll($query) as $disk) {
            $this->disks[$disk->hardware_key] = $disk;
        }
    }

    protected function fetchHardware()
    {
        $this->fetchDisks();
        $db = $this->getDb()->getDbAdapter();
        $query = $db->select()
            ->from('vm_hardware')
            ->where('vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_key');

        foreach ($db->fetchAll($query) as $row) {
            if ($row->controller_key === null) {
                $this->parents[$row->hardware_key] = $row;
            } else {
                $this->children[$row->controller_key][$row->unit_number] = $row;
            }
        }
    }

    protected function addDevices($devices, $level = 0)
    {
        foreach ($devices as $device) {
            $key = $device->hardware_key;
            $desc = $device->label;
            if ($device->summary !== $device->label) {
                $desc .= ': ' . $device->summary;
            }

            $this->addNameValueRow($key, $desc);
            if (array_key_exists($key, $this->disks)) {
                $this->addNameValueRow($key, $this->renderDisk($this->disks[$key]));
            }
            if (array_key_exists($key, $this->children)) {
                $this->addDevices($this->children[$key], $level + 1);
            }
        }
    }

    protected function renderDisk($disk)
    {
        $lookup = new PathLookup($this->getDb());
        $result = [];
        if ($disk->datastore_uuid !== null) {
            $link = $lookup->linkToObject($disk->datastore_uuid);
            if ($link instanceof Link) {
                $caption = (string) current($link->getContent());
                if ($disk->file_name) {
                    $fileName = $disk->file_name;
                    str_replace('%', '%%', $fileName);
                    $fileName = preg_replace('/\[' . preg_quote($caption, '/') . '\] /', '%s: ', $fileName);
                    $result[] = Html::sprintf($fileName, $link);
                } else {
                    $result[] = $link;
                }
            }
        }

        if ($disk->capacity !== null) {
            $result[] = Format::bytes($disk->capacity);
        }

        return $result;
    }

    protected function assemble()
    {
        $this->fetchHardware();
        $this->addDevices($this->parents);
    }
}
