<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Json\JsonString;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Table\NameValueTable;
use Icinga\Module\Vspheredb\Addon\IbmSpectrumProtect;
use Icinga\Module\Vspheredb\Addon\SimpleBackupTool;
use Icinga\Module\Vspheredb\Addon\NetBackup;
use Icinga\Module\Vspheredb\Addon\VRangerBackup;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\CustomValues;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class CustomValueDetails extends HtmlDocument
{
    use TranslationHelper;

    /** @var HostSystem|VirtualMachine */
    protected $object;

    public function __construct(BaseDbObject $object)
    {
        if (! $object instanceof HostSystem && ! $object instanceof VirtualMachine) {
            throw new InvalidArgumentException(
                'HostSystem or VirtualMachine expected, got ' . \get_class($object)
            );
        }
        $this->object = $object;
    }

    protected function assemble()
    {
        $object = $this->object;
        $this->prepend(new SubTitle($this->translate('Custom Values and Tags'), 'tags'));
        $values = $object->customValues();
        $this->stripBackupToolCustomValues($values);
        $demoMode = false;
        if ($demoMode) {
            $values = CustomValues::create([
                'Contact Persons'   => 'John Wayne, Donald Duck',
                'Application'       => 'WebSphere Application Server',
                'Installation Date' => '2020-01-02',
                'Cost Center'       => '48145',
                'Department'        => 'Web Shop',
            ]);
        }
        $tags = JsonString::decode($object->object()->get('tags'));
        if ($values->isEmpty() && empty($tags)) {
            $this->add($this->translate('No custom values have been defined'));
        } else {
            $table = NameValueTable::create($values->toArray());
            if (! empty($tags)) {
                $table->addNameValueRow([
                    Icon::create('tag'),
                    ' ',
                    $this->translate('Tags')
                ], Html::tag('ul', Html::wrapEach($tags, 'li')));
            }
            $this->add($table);
        }
    }

    protected function stripBackupToolCustomValues(CustomValues $values)
    {
        $tools = [
            new IbmSpectrumProtect(),
            new NetBackup(),
            new VRangerBackup(),
        ];

        foreach ($tools as $tool) {
            if ($tool instanceof SimpleBackupTool) {
                $tool->stripCustomValues($values);
            }
        }
    }
}
