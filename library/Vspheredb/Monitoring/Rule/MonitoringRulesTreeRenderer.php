<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class MonitoringRulesTreeRenderer extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    /** @var MonitoringRulesTree */
    protected $tree;
    protected $url;

    public function __construct(MonitoringRulesTree $tree, $url)
    {
        $this->tree = $tree;
        $this->url = $url;
    }

    protected function assemble()
    {
        $this->add($this->buildTree($this->tree->getRootNode()));
    }

    protected function buildTree($node, $level = 0): HtmlElement
    {
        $hasChildren = ! empty($node->children);
        $li = Html::tag('li');
        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        } else {
            $li->getAttributes()->add('class', 'collapsed');
        }

        $linkClasses = [$level === 0 ? 'icon-globe' : 'icon-folder-empty'];
        if ($this->tree->hasConfigurationForUuid($node->uuid)) {
            $linkClasses[] = 'configured';
        }
        $li->add($this->createLink($node->object_name, $level === 0 ? null : $node->uuid, [
            'class' => $linkClasses
        ]));

        if ($hasChildren) {
            $li->add($ul = Html::tag('ul'));
            foreach ($node->children as $child) {
                $ul->add($this->buildTree($child, $level + 1));
            }
        }

        return $li;
    }

    protected function createLink($label, $uuid = null, $attributes = []): Link
    {
        $params = [];
        if ($uuid !== null) {
            $params['uuid'] = bin2hex($uuid);
        }

        return Link::create($label, $this->url, $params, $attributes);
    }
}
