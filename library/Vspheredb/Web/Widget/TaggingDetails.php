<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Json\JsonString;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Table\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\TaggingCategory;
use Icinga\Module\Vspheredb\DbObject\TaggingObjectTag;
use Icinga\Module\Vspheredb\DbObject\TaggingTag;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use Ramsey\Uuid\Uuid;

class TaggingDetails extends HtmlDocument
{
    use TranslationHelper;

    /** @var HostSystem|VirtualMachine */
    protected $object;

    /**
     * @var TaggingTag[]
     */
    protected $tags;
    /**
     * @var TaggingCategory[]
     */
    protected $categories;

    public function __construct(BaseDbObject $object)
    {
        if (! $object instanceof HostSystem && ! $object instanceof VirtualMachine) {
            throw new InvalidArgumentException(
                'HostSystem or VirtualMachine expected, got ' . \get_class($object)
            );
        }
        $this->object = $object;
        $connection = $object->getConnection();
        $db = $connection->getDbAdapter();
        $where = $db->select()->from(['tt' => TaggingTag::TABLE], 'tt.*')
            ->join(['tot' => TaggingObjectTag::TABLE], 'tot.tag_uuid = tt.uuid', [])
            ->where('tot.object_uuid = ?', $object->get('uuid'))
            ->order('tt.name');
        $this->tags = TaggingTag::loadAll($connection, $where);
        $where = $db->select()
            ->from(['tc' => TaggingCategory::TABLE], 'tc.*')
            ->join(['tt' => TaggingTag::TABLE], 'tt.category_uuid = tc.uuid', [])
            ->join(['tot' => TaggingObjectTag::TABLE], 'tot.tag_uuid = tt.uuid', [])
            ->where('tot.object_uuid = ?', $object->get('uuid'))
            ->order('tc.name');
        $this->categories = TaggingCategory::loadAll($connection, $where);
        // $this->setDemoTags();
    }

    protected function assemble()
    {
        $this->prepend(new SubTitle($this->translate('Tags'), 'tags'));
        $internal = JsonString::decode($this->object->object()->get('tags'));
        // Simulate vCenter:
        // $internal = ['SYSTEM/COM.VMWARE.VIM.VC'];

        if (empty($this->tags) && empty($internal)) {
            $this->add($this->translate('No tags been defined'));
            return;
        }
        $table = NameValueTable::create();
        $this->add($table);

        foreach ($this->categories as $category) {
            if ($category->cardinalityIsSingle()) {
                $parent = new HtmlDocument();
            } else {
                $parent = Html::tag('ul');
            }
            foreach ($this->tags as $tag) {
                if ($tag->get('category_uuid') === $category->get('uuid')) {
                    $tagName = $tag->get('name');
                    $description = $tag->get('description');
                    if ($description !== null && $description !== '') {
                        $tagName = Html::tag('span', ['class' => 'hover-hint', 'title' => $description], $tagName);
                    }
                    if ($category->cardinalityIsSingle()) {
                        $parent->add($tagName);
                    } else {
                        $parent->add(Html::tag('li', $tagName));
                    }
                }
            }
            $table->addNameValueRow($category->get('name'), $parent);
        }

        if (! empty($internal)) {
            foreach ($internal as &$value) {
                if ($value === 'SYSTEM/COM.VMWARE.VIM.VC') {
                    $value = Html::tag('span', [
                        'class' => 'hover-hint',
                        'title' => $this->translate('This VM is running the vCenter')
                    ], $value);
                }
                // Other example, for DistributedVirtualPortgroup: has "SYSTEM/DVS.UPLINKPG" for dvUplink Portgroup
            }
            $table->addNameValueRow([
                Html::tag('i', $this->translate('Internal')),
            ], Html::tag('ul', Html::wrapEach($internal, 'li')));
        }
    }

    protected function setDemoTags()
    {
        $uuidCat1 = Uuid::fromString('a09657cb-0c0f-4c32-93de-f98a1a3e5229')->getBytes();
        $uuidCat2 = Uuid::fromString('b2272134-f552-44b4-b1c9-56fdb8d9b80b')->getBytes();
        $this->tags = [
            TaggingTag::create([
                'category_uuid' => $uuidCat1,
                'name' => 'Prod',
                'description' => 'Our production environment',
            ]),
            TaggingTag::create([
                'category_uuid' => $uuidCat2,
                'name' => 'Another Corp.',
            ]),
            TaggingTag::create([
                'category_uuid' => $uuidCat2,
                'name' => 'Contoso Inc',
            ]),
        ];
        $this->categories = [
            $uuidCat1 => TaggingCategory::create([
                'uuid' => $uuidCat1,
                'name' => 'Environment',
                'cardinality' => 'SINGLE',
            ]),
            $uuidCat2 => TaggingCategory::create([
                'uuid' => $uuidCat2,
                'name' => 'Customer',
                'cardinality' => 'MULTIPLE',
            ]),
        ];
    }
}
