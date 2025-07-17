<?php

namespace Icinga\Module\Vspheredb\Db;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\TaggingCategory;
use Icinga\Module\Vspheredb\DbObject\TaggingObjectTag;
use Icinga\Module\Vspheredb\DbObject\TaggingTag;
use stdClass;

class TagLookup
{
    /** @var Db */
    protected $db;

    /** @var array */
    protected $assignments;

    /** @var TaggingTag[] */
    protected $tags;

    /** @var TaggingCategory[] */
    protected $categories;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->assignments = $this->fetchAllAssignments();
        $this->tags = TaggingTag::loadAll($this->db, null, 'uuid');
        $this->categories = TaggingCategory::loadAll($this->db, null, 'uuid');
    }

    public function getTags(string $objectUuid): stdClass
    {
        if (!isset($this->assignments[$objectUuid])) {
            return (object) [];
        }
        $result = [];
        foreach ($this->assignments[$objectUuid] as $tagUuid) {
            if (! isset($this->tags[$tagUuid])) { // DB inconsistency, might happen during sync
                continue;
            }
            $tag = $this->tags[$tagUuid];
            $categoryUuid = $tag->get('category_uuid');
            if (! isset($this->categories[$categoryUuid])) {
                continue;
            }
            $category = $this->categories[$categoryUuid];
            if ($category->cardinalityIsSingle()) {
                $result[$category->get('name')] = $tag->get('name');
            } else {
                if (isset($result[$category->get('name')])) {
                    $result[$category->get('name')] = [$tag->get('name')];
                } else {
                    $result[$category->get('name')][] = $tag->get('name');
                }
            }
        }

        ksort($result, SORT_NATURAL);
        foreach ($result as &$values) {
            if (is_array($values)) {
                sort($values, SORT_NATURAL);
            }
        }

        return (object) $result;
    }

    protected function fetchAllAssignments(): array
    {
        $db = $this->db->getDbAdapter();
        $query = $db->select()->from(TaggingObjectTag::TABLE, ['object_uuid', 'tag_uuid']);
        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            if (isset($result[$row->object_uuid])) {
                $result[$row->object_uuid][] = $row->tag_uuid;
            } else {
                $result[$row->object_uuid] = [$row->tag_uuid];
            }
        }

        return $result;
    }
}
